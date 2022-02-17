<?php
declare(strict_types=1);

namespace IonBazan\NRIC\Tests;

use DateTime;
use IonBazan\NRIC\Exception\InvalidChecksumException;
use IonBazan\NRIC\Exception\InvalidFormatException;
use IonBazan\NRIC\NRIC;
use PHPUnit\Framework\TestCase;

class NRICTest extends TestCase
{
    public function testGenerateNric(): void
    {
        $nric = NRIC::generateNric();
        self::assertInstanceOf(NRIC::class, $nric);
        NRIC::fromString($nric->__toString());
        self::assertFalse($nric->isForeigner());
    }

    public function testGenerateFin(): void
    {
        $fin = NRIC::generateFin();
        self::assertInstanceOf(NRIC::class, $fin);
        NRIC::fromString($fin->__toString());
        self::assertTrue($fin->isForeigner());
    }

    /**
     * @dataProvider nricDatesProvider
     */
    public function testGenerateNricWithDate(string $date, string $regex): void
    {
        $nric = NRIC::generateNric(new DateTime($date));
        self::assertInstanceOf(NRIC::class, $nric);
        NRIC::fromString($nric->__toString());
        self::assertFalse($nric->isForeigner());
        self::assertMatchesRegularExpression($regex, $nric->__toString());
    }

    /**
     * @dataProvider finDatesProvider
     */
    public function testGenerateFinWithDate(string $date, string $regex): void
    {
        $fin = NRIC::generateFin(new DateTime($date));
        self::assertInstanceOf(NRIC::class, $fin);
        NRIC::fromString($fin->__toString());
        self::assertTrue($fin->isForeigner());
        self::assertMatchesRegularExpression($regex, $fin->__toString());
    }

    public function testFinRandomness(): void
    {
        $results = '';
        for ($i = 0; $i < 100; ++$i) {
            $results .= NRIC::generateFin(new DateTime('1993-12-16'))->__toString();
        }

        for ($i = 0; $i < 10; ++$i) {
            self::assertStringContainsString((string) $i, $results);
        }
    }

    public function testNricRandomness(): void
    {
        $results = [];
        for ($i = 0; $i < 10; ++$i) {
            $id = NRIC::generateNric(new DateTime('1967-12-16'));
            self::assertMatchesRegularExpression('/S0[01][0-9]{5}[A-Z]/', $id->__toString());
            $results[] = $id->__toString();
        }

        self::assertNotEmpty(array_filter($results, static function (string $id) {
            return preg_match('/S00[0-9]{5}[A-Z]/', $id);
        }));
        self::assertNotEmpty(array_filter($results, static function (string $id) {
            return preg_match('/S01[0-9]{5}[A-Z]/', $id);
        }));
    }

    /**
     * @dataProvider validIdsProvider
     */
    public function testValidIds(string $id, bool $is2000, bool $foreigner, bool $seriesM = false): void
    {
        $nric = NRIC::fromString($id);
        self::assertSame($is2000, $nric->is2000());
        self::assertSame($foreigner, $nric->isForeigner());
        self::assertSame($seriesM, $nric->isSeriesM());
    }

    /**
     * @dataProvider invalidFormatIdsProvider
     */
    public function testInvalidFormatThrowsException(string $id): void
    {
        $this->expectException(InvalidFormatException::class);
        $this->expectExceptionMessage(sprintf('ID "%s" has invalid format', $id));
        NRIC::fromString($id);
    }

    /**
     * @dataProvider invalidChecksumIdsProvider
     */
    public function testInvalidChecksumThrowsException(string $id): void
    {
        $this->expectException(InvalidChecksumException::class);
        $this->expectExceptionMessage('Invalid checksum: ' . $id);
        NRIC::fromString($id);
    }

    public function nricDatesProvider(): iterable
    {
        yield ['1993-12-16', '/S93[0-9]{5}[A-Z]/'];
        yield ['2000-01-01', '/T00[0-9]{5}[A-Z]/'];
        yield ['2002-12-16', '/T02[0-9]{5}[A-Z]/'];
        yield ['1967-12-16', '/S0[01][0-9]{5}[A-Z]/'];
        yield ['1968-01-01', '/S68[0-9]{5}[A-Z]/'];
    }

    public function finDatesProvider(): iterable
    {
        yield ['1993-12-16', '/F[0-9]{7}[A-Z]/'];
        yield ['2000-01-01', '/G[0-9]{7}[A-Z]/'];
        yield ['2002-12-16', '/G[0-9]{7}[A-Z]/'];
        yield ['2022-01-01', '/G[0-9]{7}[A-Z]/'];
        yield ['2022-12-16', '/M[0-9]{7}[A-Z]/'];
    }

    public function invalidFormatIdsProvider(): iterable
    {
        yield ['G88776991Z']; // Too long leh
        yield ['GAAAAAAAZ']; // Invalid characters
        yield ['Z1111111A']; // Invalid characters
        yield ['S6083480K']; // Invalid checksum character, pre-2000 NRIC
        yield ['F6083480B']; // Invalid checksum character, pre-2000 FIN
    }

    public function invalidChecksumIdsProvider(): iterable
    {
        yield ['T5717279A']; // post-2000 NRIC
        yield ['F6470401K']; // pre-2000 FIN
        yield ['G8877699L']; // post-2000 FIN
        yield ['M8877689K']; // post-2022 FIN
    }

    public function validIdsProvider(): iterable
    {
        yield ['S6083480F', false, false]; // pre-2000 NRIC
        yield ['T5717279C', true, false]; // post-2000 NRIC
        yield ['F6470401W', false, true]; // pre-2000 FIN
        yield ['G8877699U', true, true]; // post-2000 FIN
        yield ['M5043078W', false, true, true]; // post-2022 FIN
        yield ['M2424771J', false, true, true]; // post-2022 FIN with new J checksum letter
    }
}
