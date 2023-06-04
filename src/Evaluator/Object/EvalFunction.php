<?php

namespace Monkey\Evaluator\Object;

use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Evaluator\Environment;

class EvalFunction implements EvalObject
{
    /**
     * @param Identifier[] $parameters
     */
    public function __construct(
        public array $parameters,
        public BlockStatement $body,
        public Environment $environment,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::FUNCTION;
    }

    public function inspect(): string
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $parameters[] = $parameter->string();
        }

        return "fn(" . implode(', ', $parameters) . ") {\n{$this->body->string()}\n}";
    }
}
