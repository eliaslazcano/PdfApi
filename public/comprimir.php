<?php
/**
 * Realiza a compressão dos arquivos upados, se enviar multiplas o retorno será Zip.
 * Por padrão realiza a compressão usando o Ghostscript que altera o DPI das imagens, mas se o documento houver
 * assinaturas, a compressão é realizada com QPDF (comprime apenas headers).
 *
 * Você pode informar o parametro 'compatibilidade' = 1 para forçar o uso do motor QPDF por padrão.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\AssinaturaUtils;
use App\PdfUtils;
use App\Utils;
use Eliaslazcano\Helpers\HttpHelper;

HttpHelper::validarPost();

$compatibilidade = HttpHelper::obterParametro('compatibilidade');

if (empty($_FILES)) HttpHelper::erroJson(400, 'Nenhum arquivo enviado');

$arquivos = Utils::getAllUploadedFiles();
$arquivos = array_map(function ($i) { $i['deletar'] = false; return $i; }, $arquivos);
$compressorUtilizado = null;

try {
  //Percorre os arquivos pra comprimir um por um
  foreach ($arquivos as $index => $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) continue;
    $extensao = $file['name'] ? pathinfo($file['name'], PATHINFO_EXTENSION) : '';

    //Compressão de PDF
    if ($file['type'] === 'application/pdf' || strtolower($extensao) === 'pdf') {
      $assinaturas = AssinaturaUtils::getAssinaturas($file['tmp_name']);
      $pathTemporario = Utils::getPathTemporario('.pdf');
      if (empty($assinaturas) && !$compatibilidade) {
        $compressorUtilizado = 'ghostscript';
        PdfUtils::compressaoGhostscript($file['tmp_name'], $pathTemporario);
      } else {
        $compressorUtilizado = 'qpdf';
        PdfUtils::compressaoQpdf($file['tmp_name'], $pathTemporario);
      }
      if (!file_exists($pathTemporario)) throw new Exception('O servidor não pôde gravar o arquivo comprimido.');

      //Se a compressão do ghostscript não foi relevante, tentamos o qpdf.
      $size = filesize($pathTemporario);
      if ($size > $file['size'] && $compressorUtilizado === 'ghostscript') {
        unlink($pathTemporario);
        $compressorUtilizado = 'qpdf2';
        PdfUtils::compressaoQpdf($file['tmp_name'], $pathTemporario);
        $size = filesize($pathTemporario);
      }

      if ($size < $file['size']) {
        $arquivos[$index]['tmp_name'] = $pathTemporario;
        $arquivos[$index]['size'] = $size;
        $arquivos[$index]['deletar'] = true;
      } else unlink($pathTemporario);
    }
  }

  //Se for mais de 1 arquivo, gerar uma saída em ZIP
  if (count($arquivos) > 1) {
    $zip = new ZipArchive();
    $zipNome = Utils::getPathTemporario('.zip');
    if (!$zip->open($zipNome, ZipArchive::CREATE)) throw new Exception('Não foi possível criar o arquivo ZIP.');
    foreach ($arquivos as $file) $zip->addFile($file['tmp_name'], $file['name']);
    $zip->close();
    foreach ($arquivos as $file) if ($file['deletar']) unlink($file['tmp_name']);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=comprimido.zip');
    header('Content-Length: ' . filesize($zipNome));
    readfile($zipNome);
    unlink($zipNome);
  } else {
    if ($compressorUtilizado) header("X-Compressor: $compressorUtilizado");
    header('Content-Type: ' . $arquivos[0]['type']);
    header('Content-Disposition: attachment; filename=' . $arquivos[0]['name']);
    header('Content-Length: ' . $arquivos[0]['size']);
    readfile($arquivos[0]['tmp_name']);
    if ($arquivos[0]['deletar']) unlink($arquivos[0]['tmp_name']);
  }
  exit();
} catch (Exception $e) {
  HttpHelper::erroJson(500, $e->getMessage());
}
