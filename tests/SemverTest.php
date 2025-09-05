<?php

declare(strict_types=1);

use Modules\Support\Semver;

describe('Semver', function () {
    it('matches exact version', function () {
        expect(Semver::satisfies('1.2.3', '1.2.3'))->toBeTrue();
        expect(Semver::satisfies('1.2.3', '1.2.4'))->toBeFalse();
    });

    it('matches caret constraint', function () {
        expect(Semver::satisfies('1.2.3', '^1.2.0'))->toBeTrue();
        expect(Semver::satisfies('2.0.0', '^1.2.0'))->toBeFalse();
    });

    it('matches tilde constraint', function () {
        expect(Semver::satisfies('1.2.3', '~1.2.0'))->toBeTrue();
        expect(Semver::satisfies('1.3.0', '~1.2.0'))->toBeFalse();
    });

    it('matches greater/less constraints', function () {
        expect(Semver::satisfies('1.2.3', '>=1.2.0'))->toBeTrue();
        expect(Semver::satisfies('1.2.3', '>1.2.3'))->toBeFalse();
        expect(Semver::satisfies('1.2.3', '<1.3.0'))->toBeTrue();
        expect(Semver::satisfies('1.2.3', '<=1.2.3'))->toBeTrue();
    });

    it('matches composite AND constraints', function () {
        expect(Semver::satisfies('1.2.3', '>=1.2.0 <2.0.0'))->toBeTrue();
        expect(Semver::satisfies('2.0.0', '>=1.2.0 <2.0.0'))->toBeFalse();
    });

    it('matches composite OR constraints', function () {
        expect(Semver::satisfies('1.2.3', '^1.0.0 | ^2.0.0'))->toBeTrue();
        expect(Semver::satisfies('2.1.0', '^1.0.0 | ^2.0.0'))->toBeTrue();
        expect(Semver::satisfies('3.0.0', '^1.0.0 | ^2.0.0'))->toBeFalse();
    });

    it('matches wildcard and empty constraints', function () {
        expect(Semver::satisfies('1.2.3', '*'))->toBeTrue();
        expect(Semver::satisfies('1.2.3', ''))  ->toBeTrue();
    });
});
