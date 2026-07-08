<?php

namespace BootstrappedVentures\WPRecipeMaker\GuzzleHttp;

Use BootstrappedVentures\WPRecipeMaker\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
