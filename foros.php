<?php
include 'sistema.php';

$web = new ForoControllers;

$libros      = $web->getAllLibros();
$totalLibros = count($libros);
$per_page    = 3;
$pages       = ceil($totalLibros / $per_page);
$tmp         = array();

for ($i = 0; $i < $totalLibros; $i++) {
  $libros[$i]['intro'] = (strlen($libros[$i]['sinopsis']) > 255) ?
  substr($libros[$i]['sinopsis'], 0, 254) : $libros[$i]['sinopsis'];
  $libros[$i]['portada'] = $web->checkPortada($libros[$i]['cvelibro']);

  $web->smarty->assign('libro', $libros[$i]);
  $tmp[$i] = $web->smarty->fetch('foro.component.html');
}

$libros = array();
$cl     = 0;
for ($i = 0; $i < $pages; $i++) {
  $html = "";
  for ($j = 0; $j < $per_page; $j++) {
    if ($cl < $totalLibros) {
      $html .= $tmp[$cl];
      $cl++;
    }
  }
  $libros[$i] = $html;
}

$web->smarty->assign('fin', $pages - 1);
$web->smarty->assign('pages', $pages);
$web->smarty->assign('per_page', $per_page);
$web->smarty->assign('libros', $libros);
$web->smarty->display('foros.html');

/************************************************************************************
 * FUNCIONES
 ************************************************************************************/
