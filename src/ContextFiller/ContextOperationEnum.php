<?php

declare(strict_types=1);

namespace Argo\Serializer\ContextFiller;

enum ContextOperationEnum
{
    case Normalization;
    case Denormalization;
}
