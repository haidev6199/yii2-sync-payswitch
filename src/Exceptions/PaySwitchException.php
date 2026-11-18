<?php

namespace Haidev6199\PaySwitch\Exceptions;

/**
 * Lớp exception gốc của package. Message không chứa dữ liệu nhạy cảm (password, pin, token).
 */
class PaySwitchException extends \RuntimeException
{
}
