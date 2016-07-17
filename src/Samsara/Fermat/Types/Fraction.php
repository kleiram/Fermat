<?php

namespace Samsara\Fermat\Types;

use Samsara\Fermat\Numbers;
use Samsara\Fermat\Values\Base\NumberInterface;
use Samsara\Fermat\Values\ImmutableNumber;

abstract class Fraction
{

    protected $precision;

    protected $base;

    /**
     * @var ImmutableNumber
     */
    private $numerator;

    /**
     * @var ImmutableNumber
     */
    private $denominator;

    public function __construct($numerator, $denominator, $precision = null, $base = 10)
    {

        $this->numerator = Numbers::makeOrDont(Numbers::IMMUTABLE, $numerator)->round();
        $this->denominator = Numbers::makeOrDont(Numbers::IMMUTABLE, $denominator)->round();

    }

    public function simplify()
    {
        $gcd = $this->getGreatestCommonDivisor();

        $numerator = $this->numerator->divide($gcd);
        $denominator = $this->denominator->divide($gcd);

        return $this->setValue($numerator, $denominator);
    }

    public function add($num)
    {
        if ($num instanceof Fraction) {
            $thisNumerator = $this->numerator->multiply($num->getDenominator());
            $thatNumerator = $this->denominator->multiply($num->getNumerator());

            $finalDenominator = $this->denominator->multiply($num->getDenominator());
            $finalNumerator = $thisNumerator->add($thatNumerator);
        } else {
            $num = Numbers::makeOrDont(Numbers::IMMUTABLE, $num);

            if (!$num->isNatural()) {
                throw new \Exception('Cannot add decimal values to Fraction objects');
            }

            $finalNumerator = $this->numerator->add($num->multiply($this->denominator));
            $finalDenominator = $this->denominator;
        }

        return $this->setValue($finalNumerator, $finalDenominator);
    }

    public function getNumerator()
    {
        return $this->numerator->getValue();
    }

    public function getDenominator()
    {
        return $this->denominator->getValue();
    }

    /**
     * @return NumberInterface
     */
    protected function getGreatestCommonDivisor()
    {
        if (function_exists('gmp_gcd') && function_exists('gmp_strval')) {
            $val = gmp_strval(gmp_gcd($this->numerator->getValue(), $this->denominator->getValue()));

            return Numbers::make(Numbers::IMMUTABLE, $val);
        } else {
            $numerator = $this->numerator->abs();
            $denominator = $this->denominator->abs();

            if ($numerator->lessThan($denominator)) {
                $greater = $denominator;
                $lesser = $numerator;
            } else {
                $greater = $numerator;
                $lesser = $denominator;
            }

            $remainder = $greater->modulo($lesser);

            while ($remainder->greaterThan(0)) {
                $greater = $lesser;
                $lesser = $remainder;
                $remainder = $greater->modulo($lesser);
            }

            return $lesser;
        }
    }

    /**
     * @param ImmutableNumber $numerator
     * @param ImmutableNumber $denominator
     * @return Fraction
     */
    abstract protected function setValue(ImmutableNumber $numerator, ImmutableNumber $denominator);

}