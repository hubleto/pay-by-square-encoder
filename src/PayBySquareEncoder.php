<?php

namespace Hubleto\Utilities;

class PayBySquareEncoder
{
  private float $amount = 0.00;
  private string $currency = "EUR";
  private string $paymentDate;
  private string $vs;
  private string $cs;
  private string $ss;
  private string $paymentRef;
  private string $note;
  private string $iban;
  private string $bic;
  private string $beneficiaryName;
  private string $beneficiaryAddress1;
  private string $beneficiaryAddress2;

  private string $xzPath;


  public function setAmount(float $amount): self
  {
    $this->amount = $amount;
    return $this;
  }

  public function setCurrency(string $currency): self
  {
    $this->currency = $currency;
    return $this;
  }

  public function setPaymentDate(\DateTime $date): self
  {
    $this->paymentDate = $date->format("Ymd");
    return $this;
  }

  public function setVariableSymbol(string $vs): self
  {
    $this->vs = $vs;
    return $this;
  }

  public function setConstantSymbol(string $cs): self
  {
    $this->cs = $cs;
    return $this;
  }

  public function setSpecificSymbol(string $ss): self
  {
    $this->ss = $ss;
    return $this;
  }

  public function setPaymentRef(string $paymentRef): self
  {
    $this->paymentRef = $paymentRef;
    return $this;
  }

  public function setNote(string $note): self
  {
    $this->note = $note;
    return $this;
  }

  public function setIBAN(string $iban): self
  {
    $this->iban = $iban;
    return $this;
  }

  public function setBIC(string $bic): self
  {
    $this->bic = $bic;
    return $this;
  }

  public function setBeneficiaryName(string $beneficiaryName): self
  {
    $this->beneficiaryName = $beneficiaryName;
    return $this;
  }

  public function setBeneficiaryAddress1(string $beneficiaryAddress1): self
  {
    $this->beneficiaryAddress1 = $beneficiaryAddress1;
    return $this;
  }

  public function setBeneficiaryAddress2(string $beneficiaryAddress2): self
  {
    $this->beneficiaryAddress2 = $beneficiaryAddress2;
    return $this;
  }

  public function setXzPath(string $xzPath): self
  {
    $this->xzPath = $xzPath;
    return $this;
  }

  private function getRawData(): string
  {
    $subValues = [
      "1",
      $this->amount ?? 0.01,
      $this->currency ?? "EUR",
      $this->paymentDate ?? (new \DateTime())->format("Ymd"),
      $this->vs ?? "",
      $this->cs ?? "",
      $this->ss ?? "",
      $this->paymentRef ?? "",
      $this->note ?? "",
      "1",
      $this->iban ?? "",
      $this->bic ?? "",
      "0",
      "0",
      $this->beneficiaryName ?? "",
      $this->beneficiaryAddress1 ?? "",
      $this->beneficiaryAddress2 ?? ""
    ];

    $values = [
      '',
      1,
      join("\t", $subValues),
    ];

    return join("\t", $values);
  }

  public function getEncodedString(): string
  {
    // get raw data
    $rawData = $this->getRawData();

    // merge rawData and its 32-bit crc into a payload
    $payload = strrev(hash("crc32b", $rawData, TRUE)) . $rawData;

    // compress payload with LZMA

    $cmd = 
      $this->xzPath
      . " --format=raw --lzma1=lc=3,lp=0,pb=2,dict=128KiB -c -"
    ;

    $descriptors = [ ["pipe", "r"], ["pipe", "w"], ["pipe", "w"] ];
    $proc = proc_open($cmd, $descriptors, $pipes);
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);

    $lzmaRaw = stream_get_contents($pipes[1]);
    $lzmaErr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($proc);

    if ($exitCode != 0) {
      throw new \Exception("XZ in PayBySquareEncoded exited with exit code {$exitCode}");
    }

    // encode to hex
    $hex = bin2hex("\x00\x00" . pack("v", strlen($payload)) . $lzmaRaw);

    return $this->padAndTranslate($hex);
  }

  private function padAndTranslate(string $data): string
  {
    $len = strlen($data);
    $b = "";
    for ($i = 0; $i < $len; $i++) {
      $b .= str_pad(base_convert($data[$i], 16, 2), 4, "0", STR_PAD_LEFT);
    }

    $l = strlen($b);
    $r = $l % 5;
    if ($r > 0) {
      $p = 5 - $r;
      $b .= str_repeat("0", $p);
      $l += $p;
    }

    $l = $l / 5;
    $data = str_repeat("_", $l);

    for ($i = 0; $i < $l; ++$i) {
      $data[$i] = "0123456789ABCDEFGHIJKLMNOPQRSTUV"[bindec(substr($b, $i * 5, 5))];
    }

    return $data;
  }
}