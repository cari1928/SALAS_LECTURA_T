<?php

require '../lib/dompdf/vendor/autoload.php';
use Dompdf\Dompdf;

/**
 * PDF Structure
 */
class PDF extends Sistema
{
  public function createPDF($title, $content)
  {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($title . '.pdf', array('Attachment' => 0));
  }
}

$pdf = new PDF;