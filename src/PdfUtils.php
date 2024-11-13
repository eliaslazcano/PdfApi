<?php

namespace App;

class PdfUtils
{
  const string GHOSTSCRIPT_QUALIDADE_DPI72 = 'screen';
  const string GHOSTSCRIPT_QUALIDADE_DPI150 = 'ebook';
  const string GHOSTSCRIPT_QUALIDADE_DPI300 = 'printer';

  /**
   * Comprime um arquivo PDF usando o Ghostscript.
   * @param string $pathOrigem - Caminho até o arquivo original.
   * @param string $pathSaida - Caminho até o arquivo resultante.
   * @param string $qualidade - Qualidade das imagens no documento, ajustando o DPI.
   * @return bool - Sucesso ao criar o arquivo.
   */
  public static function compressaoGhostscript(string $pathOrigem, string $pathSaida, string $qualidade = self::GHOSTSCRIPT_QUALIDADE_DPI150): bool
  {
    $comando = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/$qualidade -dNOPAUSE -dQUIET -dBATCH -dAutoRotatePages=/None -sOutputFile=$pathSaida $pathOrigem";
    shell_exec($comando);
    return file_exists($pathSaida);
  }

  /**
   * Comprime um arquivo PDF usando o QPDF.
   * @param string $pathOrigem - Caminho até o arquivo original.
   * @param string $pathSaida - Caminho até o arquivo resultante.
   * @return bool - Sucesso ao criar o arquivo.
   */
  public static function compressaoQpdf(string $pathOrigem, string $pathSaida): bool
  {
    $comando = "qpdf --compress-streams=y --object-streams=generate $pathOrigem $pathSaida";
    shell_exec($comando);
    return file_exists($pathSaida);
  }
}