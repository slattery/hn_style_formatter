<?php

namespace Drupal\hn_style_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'hn_style_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "hn_style_formatter",
 *   label = @Translation("Plain Text with Linklist"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class HnStyleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $footnotes = [];
    $footnote_counter = 1;

    foreach ($items as $delta => $item) {
      $text = $item->value;

      $baseline = strip_tags($text, '<a>');
      // Regular expression to find links in HTML
      $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1>(.*?)<\/a>/i';

      // Callback function to replace links with footnote references
      $processed_text = preg_replace_callback($pattern, function($matches) use (&$footnotes, &$footnote_counter) {
        $url = $matches[2];
        $link_text = $matches[3];

        //a [hobbit-hole][1], and that means comfort.
        //[1]: <https://en.wikipedia.org/wiki/Hobbit#Lifestyle> "Hobbit lifestyles"

        // Add to footnotes array
        $footnotes[] = [
          'number' => $footnote_counter,
          'url' => $url,
          'text' => strip_tags($link_text)
        ];

        // Replace link with text and footnote reference
        return '[' . $link_text . '][' . $footnote_counter++ . ']';
      }, $baseline);

      // Build the output with text and footnotes
      $output = [
        '#type' => 'markup',
        '#markup' => $processed_text,
      ];

      // Add footnotes section if there are any
      if (!empty($footnotes)) {
        $footnotes_html = "";
        foreach ($footnotes as $footnote) {
          $footnotes_html .= sprintf(
            "[%d]: %s\n",
            $footnote['number'],
            $footnote['url']
          );
        }
        $output['#markup'] .= $footnotes_html;
      }

      $elements[$delta] = $output;
    }

    return $elements;
  }
}
