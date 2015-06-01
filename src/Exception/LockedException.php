<?php

namespace HF\POOL\Exception;

class LockedException extends \RuntimeException {
    const MESSAGE_LOCKED = "Object (%s:%s) is locked";
}