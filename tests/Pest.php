<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

expect()->extend('toBeLetStatement', function (string $name, $value) {
    expect($this->value)->toBeInstanceOf(LetStatement::class);
    expect($this->value->tokenLiteral())->toBe('let');
    expect($this->value->name)->toBeLiteralExpression(Identifier::class, $name);
    expect($this->value->value)->toBeExpression(...$value);
});

expect()->extend('toBeReturnStatement', function ($value) {
    expect($this->value)->toBeInstanceOf(ReturnStatement::class);
    expect($this->value->tokenLiteral())->toBe('return');
    expect($this->value->value)->toBeExpression(...$value);
});

expect()->extend('toBeExpressionStatement', function (string $expression, ...$args) {
    expect($this->value)->toBeInstanceOf(ExpressionStatement::class);
    expect($this->value->value)->toBeExpression($expression, ...$args);
});

expect()->extend('toBeExpression', function (string $expression, ...$args) {
    match ($expression) {
        Boolean::class => expect($this->value)->toBeBoolean(...$args),
        Identifier::class => expect($this->value)->toBeIdentifier(...$args),
        IntegerLiteral::class => expect($this->value)->toBeIntegerLiteral(...$args),
        InfixExpression::class => expect($this->value)->toBeInfixExpression(...$args),
        PrefixExpression::class => expect($this->value)->toBePrefixExpression(...$args),
        IfExpression::class => expect($this->value)->toBeIfExpression(...$args),
        FunctionLiteral::class => expect($this->value)->toBeFunctionLiteral(...$args),
        CallExpression::class => expect($this->value)->toBeCallExpression(...$args),
    };
});

expect()->extend('toBeLiteralExpression', function (string $expression, string|int|bool $value) {
    match ($expression) {
        Boolean::class => expect($this->value)->toBeBoolean($value),
        Identifier::class => expect($this->value)->toBeIdentifier($value),
        IntegerLiteral::class => expect($this->value)->toBeIntegerLiteral($value),
    };
});

expect()->extend('toBeBoolean', function (bool $value) {
    expect($this->value)->toBeInstanceOf(Boolean::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe($value ? 'true' : 'false');
});

expect()->extend('toBeIdentifier', function (string $value) {
    expect($this->value)->toBeInstanceOf(Identifier::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe($value);
});

expect()->extend('toBeIntegerLiteral', function (int $value) {
    expect($this->value)->toBeInstanceOf(IntegerLiteral::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe("{$value}");
});

expect()->extend('toBeInfixExpression', function ($left, string $operator, $right) {
    expect($this->value)->toBeInstanceOf(InfixExpression::class);
    expect($this->value->left)->toBeExpression(...$left);
    expect($this->value->operator)->toBe($operator);
    expect($this->value->right)->toBeExpression(...$right);
});

expect()->extend('toBePrefixExpression', function (string $operator, $right) {
    expect($this->value)->toBeInstanceOf(PrefixExpression::class);
    expect($this->value->operator)->toBe($operator);
    expect($this->value->right)->toBeExpression(...$right);
});

expect()->extend('toBeIfExpression', function ($condition, array $consequences, array $alternatives) {
    expect($this->value)->toBeInstanceOf(IfExpression::class);
    expect($this->value->condition)->toBeExpression(...$condition);
    expect($this->value->consequence->statements)->toHaveCount(count($consequences));
    foreach ($this->value->consequence->statements as $i => $consequence) {
        expect($consequence)->toBeExpressionStatement(...$consequences[$i]);
    }
    if (count($alternatives) > 0) {
        expect($this->value->alternative)->not->toBeNull();
        expect($this->value->alternative->statements)->toHaveCount(count($alternatives));
        foreach ($this->value->alternative->statements as $i => $alternative) {
            expect($alternative)->toBeExpressionStatement(...$alternatives[$i]);
        }
    } else {
        expect($this->value->alternative)->toBeNull();
    }
});

expect()->extend('toBeFunctionLiteral', function (array $parameters, array $body) {
    expect($this->value)->toBeInstanceOf(FunctionLiteral::class);
    expect($this->value->parameters)->toHaveCount(count($parameters));
    foreach ($this->value->parameters as $i => $parameter) {
        expect($parameter)->toBeLiteralExpression(...$parameters[$i]);
    }
    expect($this->value->body->statements)->toHaveCount(count($body));
    foreach ($this->value->body->statements as $i => $statement) {
        expect($statement)->toBeExpressionStatement(...$body[$i]);
    }
});

expect()->extend('toBeCallExpression', function ($function, array $arguments) {
    expect($this->value)->toBeInstanceOf(CallExpression::class);
    expect($this->value->function)->toBeExpression(...$function);
    expect($this->value->arguments)->toHaveCount(count($arguments));
    foreach ($this->value->arguments as $i => $argument) {
        expect($argument)->toBeExpression(...$arguments[$i]);
    }
});

// expect()->extend('toEvaluateTo', function ($expected) {
//     expect($evaluated->type())->toBeInstanceOf($expected[0]);
//     expect($evaluated->value)->toBe($expected[1]);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createProgram(string $input, int $count = 1): Program
{
    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    // expect($program->statements)->toHaveCount($count);

    return $program;
}
