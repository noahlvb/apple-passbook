<?php

declare(strict_types=1);

namespace LauLamanApps\ApplePassbook\Build;

use LauLamanApps\ApplePassbook\Passbook;
use LogicException;
use Ramsey\Uuid\Uuid;

final class Compiler
{
    public const PASS_DATA_FILE = 'pass.json';

    /**
     * @var ManifestGenerator
     */
    private $manifestGenerator;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Compressor
     */
    private $compressor;

    /**
     * @var string
     */
    private $passTypeIdentifier;

    /**
     * @var string
     */
    private $teamIdentifier;

    public function __construct(
        ManifestGenerator $manifestGenerator,
        Signer $signer,
        Compressor $compressor,
        ?string $passTypeIdentifier = null,
        ?string $teamIdentifier = null
    ) {
        $this->manifestGenerator = $manifestGenerator;
        $this->signer = $signer;
        $this->compressor = $compressor;
        $this->passTypeIdentifier = $passTypeIdentifier;
        $this->teamIdentifier = $teamIdentifier;
    }

    public function setPassTypeIdentifier(string $passTypeIdentifier): void
    {
        $this->passTypeIdentifier = $passTypeIdentifier;
    }

    public function setTeamIdentifier(string $teamIdentifier): void
    {
        $this->teamIdentifier = $teamIdentifier;
    }

    public function compile(Passbook $passbook): string
    {
        $this->validate($passbook);

        if (!$passbook->hasPassTypeIdentifier()) {
            $passbook->setPassTypeIdentifier($this->passTypeIdentifier);
        }

        if (!$passbook->hasTeamIdentifier()) {
            $passbook->setTeamIdentifier($this->teamIdentifier);
        }

        $temporaryDirectory = $this->createTemporaryDirectory();

        try {
            $this->manifestGenerator->generate($passbook, $temporaryDirectory);
            $this->signer->sign($passbook, $temporaryDirectory);
            $this->compressor->compress($passbook, $temporaryDirectory);

            $compiled = file_get_contents($temporaryDirectory . Compressor::FILENAME);
        } finally {
            $this->cleanup($temporaryDirectory);
        }

        return $compiled;
    }

    private function validate(Passbook $passbook): void
    {
        if ($this->passTypeIdentifier === null && $passbook->hasPassTypeIdentifier() === false) {
            throw new LogicException('PassTypeIdentifier is unknown. Either specify it on the passbook and/or specify it in the compiler.');
        }

        if ($this->teamIdentifier === null && $passbook->hasTeamIdentifier() === false) {
            throw new LogicException('TeamIdentifier is unknown. Either specify it on the passbook and/or specify it in the compiler.');
        }
    }

    private function createTemporaryDirectory(): string
    {
        $dir = sprintf('%s/passbook_%s/', sys_get_temp_dir(), Uuid::uuid4()->toString());

        mkdir($dir);

        return $dir;
    }

    private function cleanup(string $temporaryDirectory): void
    {
        $files = array_diff(scandir($temporaryDirectory), ['..', '.']);
        foreach ($files as $file) {
            unlink($temporaryDirectory . $file);
        }

        rmdir($temporaryDirectory);
    }
}
