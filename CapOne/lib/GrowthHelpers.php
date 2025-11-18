<?php

if (!function_exists('normalizeSex')) {
    function normalizeSex(?string $sex): ?string
    {
        if ($sex === null) {
            return null;
        }

        $sexTrimmed = strtolower(trim($sex));

        if ($sexTrimmed === '') {
            return null;
        }

        if (in_array($sexTrimmed, ['male', 'm'], true)) {
            return 'Male';
        }

        if (in_array($sexTrimmed, ['female', 'f'], true)) {
            return 'Female';
        }

        return null;
    }
}

if (!function_exists('fetchGrowthReference')) {
    function fetchGrowthReference(int $ageInMonths, mysqli $con, ?string $sex = null): ?array
    {
        $reference = null;

        if ($sex !== null) {
            $stmt = $con->prepare('SELECT weight_min, weight_max, height_min, height_max FROM growth_reference WHERE age_in_months = ? AND sex = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('is', $ageInMonths, $sex);
                $stmt->execute();
                $result = $stmt->get_result();
                $reference = $result ? $result->fetch_assoc() : null;
                $stmt->close();
            }
        }

        if ($reference) {
            return $reference;
        }

        $stmt = $con->prepare('SELECT weight_min, weight_max, height_min, height_max FROM growth_reference WHERE age_in_months = ? AND (sex IS NULL OR sex = "") LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $ageInMonths);
            $stmt->execute();
            $result = $stmt->get_result();
            $reference = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }

        return $reference ?: null;
    }
}

if (!function_exists('classifyGrowth')) {
    function classifyGrowth(int $age, float $weight, float $height, mysqli $con, ?array $referenceData = null, ?string $sex = null): string
    {
        if ($referenceData === null) {
            $referenceData = fetchGrowthReference($age, $con, $sex);
        }

        if (!$referenceData) {
            return 'No reference data';
        }

        $statuses = [];

        if ($weight < (float) $referenceData['weight_min']) {
            $statuses[] = 'Underweight';
        } elseif ($weight > (float) $referenceData['weight_max']) {
            $statuses[] = 'Overweight';
        } else {
            $statuses[] = 'Normal Weight';
        }

        if ($height < (float) $referenceData['height_min']) {
            $statuses[] = 'Stunted';
        } elseif ($height > (float) $referenceData['height_max']) {
            $statuses[] = 'Tall';
        } else {
            $statuses[] = 'Normal Height';
        }

        return implode(', ', $statuses);
    }
}

if (!function_exists('computeStatus')) {
    function computeStatus($growthStatus, $previousWeight, $previousHeight, $currentWeight, $currentHeight)
    {
        if (!empty($growthStatus)) {
            return $growthStatus;
        }

        if ($currentWeight === null || $currentHeight === null) {
            return 'Pending';
        }

        $previousWeight = (float) $previousWeight;
        $previousHeight = (float) $previousHeight;
        $currentWeight = (float) $currentWeight;
        $currentHeight = (float) $currentHeight;

        $weightDiff = $currentWeight - $previousWeight;
        $heightDiff = $currentHeight - $previousHeight;

        if ($weightDiff > 0.0 || $heightDiff > 0.0) {
            return 'Improving';
        }

        if ($weightDiff < 0.0 || $heightDiff < 0.0) {
            return 'Needs Attention';
        }

        if ($currentWeight === 0.0 && $currentHeight === 0.0) {
            return 'Pending';
        }

        return 'Maintained';
    }
}

if (!function_exists('computeAgeInMonths')) {
    function computeAgeInMonths(?string $dateOfBirth): ?int
    {
        if ($dateOfBirth === null || trim($dateOfBirth) === '') {
            return null;
        }

        try {
            $dob = new DateTime($dateOfBirth);
            $today = new DateTime();
            $diff = $dob->diff($today);
            return ($diff->y * 12) + $diff->m;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('formatMeasurement')) {
    function formatMeasurement($value)
    {
        if ($value === null || $value === '') {
            return '--';
        }

        return number_format((float) $value, 1);
    }
}
