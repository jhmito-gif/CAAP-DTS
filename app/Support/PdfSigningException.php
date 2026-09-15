<?php

namespace App\Support;

use RuntimeException;

/**
 * A document could not be signed; the message is safe to show to the signer.
 */
class PdfSigningException extends RuntimeException
{
}
