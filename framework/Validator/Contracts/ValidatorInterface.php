<?php

namespace Framework\Kernel\Validator\Contracts;

use Framework\Kernel\Application\Contracts\ApplicationInterface;
use Framework\Kernel\Translation\Contracts\TranslatorInterface;
use Framework\Kernel\Validator\Bags\MessageBag;

interface ValidatorInterface
{
    public function validated(): array;

    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): static;

    public function setPresenceVerifier(?PresenceVerifierInterface $verifier): static;

    public function setContainer(?ApplicationInterface $application): static;

    public function fails(): bool;

    public function errors(): MessageBag;

    public function getException(): string;

    public function getTranslator(): TranslatorInterface;

}