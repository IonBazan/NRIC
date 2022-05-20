<?php
declare(strict_types=1);

namespace IonBazan\NRIC;

use DateTime;
use DateTimeInterface;
use IonBazan\NRIC\Exception\InvalidChecksumException;
use IonBazan\NRIC\Exception\InvalidFormatException;
use Stringable;

final class NRIC implements Stringable
{
    private const CHECKSUM_WEIGHTS = [2, 7, 6, 5, 4, 3, 2];
    private const LENGTH = 9;
    private const CHECKSUM_CITIZEN = 'JZIHGFEDCBA';
    private const CHECKSUM_FOREIGNER = 'XWUTRQPNMLK';
    private const CHECKSUM_FOREIGNER_2022 = 'XWUTRQPNJLK';
    private const PREFIX_CITIZEN_1900 = 'S';
    private const PREFIX_CITIZEN_2000 = 'T';
    private const PREFIX_FOREIGNER_1900 = 'F';
    private const PREFIX_FOREIGNER_2000 = 'G';
    private const PREFIX_FOREIGNER_2022 = 'M';
    private const CUTOFF_DATE = '1968-01-01';

    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @throws InvalidFormatException
     * @throws InvalidChecksumException
     */
    public static function fromString(string $nric): self
    {
        $instance = new self($nric);
        $instance->validate();

        return $instance;
    }

    public static function generateNric(?DateTimeInterface $issueDate = null): self
    {
        return self::generateId($issueDate, false);
    }

    public static function generateFin(?DateTimeInterface $issueDate = null): self
    {
        return self::generateId($issueDate, true);
    }

    private static function generateId(?DateTimeInterface $issueDate, bool $foreigner): self
    {
        $issueDate ??= self::getRandomDate();
        $id = self::getPrefix($issueDate, $foreigner);

        for ($i = strlen($id); $i < self::LENGTH; $i++) {
            $id .= random_int(0, 9);
        }

        $result = new self($id);
        $result->addChecksum();

        return $result;
    }

    public function isForeigner(): bool
    {
        return in_array($this->id[0], [self::PREFIX_FOREIGNER_1900, self::PREFIX_FOREIGNER_2000, self::PREFIX_FOREIGNER_2022], true);
    }

    public function is2000(): bool
    {
        return in_array($this->id[0], [self::PREFIX_CITIZEN_2000, self::PREFIX_FOREIGNER_2000], true);
    }

    public function isSeriesM(): bool
    {
        return self::PREFIX_FOREIGNER_2022 === $this->id[0];
    }

    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * @throws InvalidFormatException
     * @throws InvalidChecksumException
     */
    private function validate(): void
    {
        $regex = vsprintf('/^([%s%s][\d]{7}[%s])|([%s%s%s][\d]{7}[%s%s])$/', [
            self::PREFIX_CITIZEN_1900,
            self::PREFIX_CITIZEN_2000,
            self::CHECKSUM_CITIZEN,
            self::PREFIX_FOREIGNER_1900,
            self::PREFIX_FOREIGNER_2000,
            self::PREFIX_FOREIGNER_2022,
            self::CHECKSUM_FOREIGNER,
            self::CHECKSUM_FOREIGNER_2022
        ]);

        if (preg_match($regex, $this->id) === 0) {
            throw new InvalidFormatException($this->id);
        }

        if ($this->generateChecksum() !== $this->id[self::LENGTH - 1]) {
            throw new InvalidChecksumException($this->id);
        }
    }

    private function addChecksum(): void
    {
        $this->id[self::LENGTH - 1] = $this->generateChecksum();
    }

    private function generateChecksum(): string
    {
        $checksum = $this->getOffset();

        foreach (self::CHECKSUM_WEIGHTS as $key => $weight) {
            $checksum += $this->id[$key+1] * $weight; // @phpstan-ignore-line
        }

        return $this->getChecksumChars()[$checksum % 11];
    }

    private function getOffset(): int
    {
        if ($this->isSeriesM()) {
            return 3;
        }

        if ($this->is2000()) {
            return 4;
        }

        return 0;
    }

    private function getChecksumChars(): string
    {
        if ($this->isSeriesM()) {
            return self::CHECKSUM_FOREIGNER_2022;
        }

        if ($this->isForeigner()) {
            return self::CHECKSUM_FOREIGNER;
        }

        return self::CHECKSUM_CITIZEN;
    }

    private static function getRandomDate(): DateTimeInterface
    {
        $minTimestamp = (new DateTime('1900-01-01'))->getTimestamp();
        $maxTimestamp = (new DateTime())->getTimestamp();

        return new DateTime('@' . random_int($minTimestamp, $maxTimestamp));
    }

    private static function getPrefix(DateTimeInterface $issueDate, bool $foreigner): string
    {
        if ($foreigner) {
            if ($issueDate > new DateTime('2022-01-01')) {
                return self::PREFIX_FOREIGNER_2022;
            }

            if ($issueDate < new DateTime('2000-01-01')) {
                return self::PREFIX_FOREIGNER_1900;
            }

            return self::PREFIX_FOREIGNER_2000;
        }

        $prefix = ($issueDate < new DateTime('2000-01-01')) ? self::PREFIX_CITIZEN_1900 : self::PREFIX_CITIZEN_2000;

        // NRICs before 1968 did not contain YOB
        return $prefix . (($issueDate < new DateTime(self::CUTOFF_DATE)) ? '0'. random_int(0, 1) : $issueDate->format('y'));
    }
}
