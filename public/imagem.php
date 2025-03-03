<?php
/**
 * POST => Converte uma ou mais imagens em PDF. Retorna zip se forem varias.
 */

use App\PdfUtils;
use App\Utils;
use Eliaslazcano\Helpers\HttpHelper;

HttpHelper::validarPost();

if (empty($_FILES)) HttpHelper::erroJson(400, 'Nenhum arquivo enviado');

$arquivos = Utils::getAllUploadedFiles();
if (count($arquivos) === 1) {
  $pathTemporario = Utils::getPathTemporario('.pdf');
  $file = reset($arquivos);
  $sucesso = PdfUtils::converterImagem($file['tmp_name'], $pathTemporario);
  if (!$sucesso) throw new Exception('O servidor não pôde gravar o arquivo comprimido.');

  $nomeOriginal = pathinfo($file['tmp_name'], PATHINFO_FILENAME);

  header('Content-Type: application/pdf');
  header('Content-Length: ' . filesize($pathTemporario));
  header("Content-Disposition: attachment; filename=$nomeOriginal.pdf");
  readfile($pathTemporario);
  unlink($pathTemporario);
} else {
  $pathsTemporarios = [];
  foreach ($arquivos as $file) {
    $pathTemporario = Utils::getPathTemporario('.pdf');
    $sucesso = PdfUtils::converterImagem($file['tmp_name'], $pathTemporario);
    if (!$sucesso) throw new Exception('O servidor não pôde gravar o arquivo comprimido.');
    $nomeOriginal = pathinfo($file['tmp_name'], PATHINFO_FILENAME);
    $pathsTemporarios[] = ['path' => $pathTemporario, 'nome' => "$nomeOriginal.pdf"];
  }

  $zip = new ZipArchive();
  $zipNome = Utils::getPathTemporario('.zip');
  if (!$zip->open($zipNome, ZipArchive::CREATE)) throw new Exception('Não foi possível criar o arquivo ZIP.');
  foreach ($pathsTemporarios as $i) $zip->addFile($i['path'], $i['nome']);
  $zip->close();

  foreach ($pathsTemporarios as $i) unlink($i['path']);
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename=imagens.zip');
  header('Content-Length: ' . filesize($zipNome));
  readfile($zipNome);
  unlink($zipNome);
}