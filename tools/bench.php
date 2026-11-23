<?php
declare(strict_types=1);

/**
 * Micro-benchmark of the checks a typed collection makes: construction, `append`, `offsetSet` and `accepts`, against an
 * untyped ArrayObject (the floor) and a plain loop with `is_*` (what a hand-written check costs).
 * Usage: php tools/bench.php [autoload.php]
 */
require $argv[1] ?? __DIR__ . '/../vendor/autoload.php';

use TypedArray\{IntegerArray, StringArray, TypedArray};

final class Tag { public function __construct(public string $name = 'x') {} }
final class TagArray extends TypedArray { protected string $expected_type = Tag::class; }
final class IdOrName extends TypedArray { protected string $expected_type = 'int|string'; }

function time_it(string $label, int $repeat, callable $work, ?float $floor = null): float
{
    $work(); // warm up
    $best = INF;
    for ($round = 0; $round < 5; ++$round) {
        $start = hrtime(true);
        for ($i = 0; $i < $repeat; ++$i) {
            $work();
        }
        $best = min($best, (hrtime(true) - $start) / $repeat / 1e3);
    }
    printf("  %-44s %9.2f µs%s\n", $label, $best, $floor !== null && $floor > 0 ? sprintf('   %6.1f× the floor', $best / $floor) : '');
    return $best;
}

foreach ([['5 items (a list in an answer)', 5, 40000], ['100 items', 100, 4000], ['10,000 items', 10000, 60]] as [$name, $size, $repeat]) {
    echo "\n== construct, $name\n";
    $ints = range(1, $size);
    $strings = array_map(static fn (int $i): string => "s$i", $ints);
    $tags = array_map(static fn (int $i): Tag => new Tag("t$i"), $ints);
    $mixed = array_map(static fn (int $i): int|string => $i % 2 ? $i : "s$i", $ints);
    $floor = time_it('ArrayObject (untyped, the floor)', $repeat, static fn () => new ArrayObject($ints));
    time_it('hand-written loop + is_int, then ArrayObject', $repeat, static function () use ($ints): ArrayObject { foreach ($ints as $v) { if (!is_int($v)) { throw new TypeError(); } } return new ArrayObject($ints); }, $floor);
    time_it('IntegerArray', $repeat, static fn () => new IntegerArray($ints), $floor);
    time_it('StringArray', $repeat, static fn () => new StringArray($strings), $floor);
    time_it('class-typed (Tag)', $repeat, static fn () => new TagArray($tags), $floor);
    time_it('union int|string', $repeat, static fn () => new IdOrName($mixed), $floor);
}

echo "\n== writes and queries (one operation)\n";
$array = new IntegerArray(range(1, 10));
$tags = new TagArray([new Tag()]);
$tag = new Tag();
time_it('IntegerArray::append (1 item)', 200000, static function () use ($array): void { $array->append(1); });
time_it('IntegerArray offsetSet', 200000, static function () use ($array): void { $array[0] = 5; });
time_it('TagArray::append', 200000, static function () use ($tags, $tag): void { $tags->append($tag); });
time_it('IntegerArray::accepts', 400000, static function () use ($array): void { $array->accepts(5); });
time_it('plain ArrayObject::append (the floor)', 200000, static function (): void { static $a = null; $a ??= new ArrayObject(); $a->append(1); });
