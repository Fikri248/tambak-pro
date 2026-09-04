const decimalPattern = /^([+-]?)(\d+)(?:\.(\d+))?$/;

export const normalizeDecimal = (value, fallback = '') => {
    const decimal = String(value ?? '').trim();
    if (!decimal) return fallback;

    const match = decimal.match(decimalPattern);
    if (!match) return decimal;

    const sign = match[1];
    const integer = match[2];
    const fraction = (match[3] ?? '').replace(/0+$/, '');
    if (!/[1-9]/.test(`${integer}${fraction}`)) return '0';

    return `${sign}${integer}${fraction ? `.${fraction}` : ''}`;
};

export const formatLocalizedDecimal = (value, fallback = '—') => {
    const decimal = normalizeDecimal(value, fallback);
    const match = decimal.match(decimalPattern);
    if (!match) return decimal;

    const integer = match[2].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const fraction = match[3] ?? '';
    return `${match[1]}${integer}${fraction ? `,${fraction}` : ''}`;
};

export const multiplyDecimals = (leftValue, rightValue, maximumFractionDigits = 2) => {
    const left = normalizeDecimal(leftValue, '0');
    const right = normalizeDecimal(rightValue, '0');
    const leftMatch = left.match(decimalPattern);
    const rightMatch = right.match(decimalPattern);
    if (!leftMatch || !rightMatch) return '0';

    const negative = (leftMatch[1] === '-') !== (rightMatch[1] === '-');
    const leftFraction = leftMatch[3] ?? '';
    const rightFraction = rightMatch[3] ?? '';
    let product = BigInt(`${leftMatch[2]}${leftFraction}`) * BigInt(`${rightMatch[2]}${rightFraction}`);
    let scale = leftFraction.length + rightFraction.length;

    if (scale > maximumFractionDigits) {
        const divisor = 10n ** BigInt(scale - maximumFractionDigits);
        const remainder = product % divisor;
        product /= divisor;
        if (remainder * 2n >= divisor) product += 1n;
        scale = maximumFractionDigits;
    } else if (scale < maximumFractionDigits) {
        product *= 10n ** BigInt(maximumFractionDigits - scale);
        scale = maximumFractionDigits;
    }

    let digits = product.toString().padStart(scale + 1, '0');
    if (scale > 0) digits = `${digits.slice(0, -scale)}.${digits.slice(-scale)}`;

    return normalizeDecimal(`${negative && product !== 0n ? '-' : ''}${digits}`, '0');
};
