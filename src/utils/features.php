<?php

function is_feature_enabled(string $featureName): bool {
    $envVarName = 'FEATURE_' . strtoupper($featureName);
    $envFlag = getenv($envVarName);
    
    // Default behaviors: if not explicitly configured, enabled in dev, disabled in prod.
    if ($envFlag === false) {
        $isProduction = (getenv('VERCEL_ENV') === 'production' || getenv('APP_ENV') === 'production');
        return !$isProduction;
    }
    
    $envFlagLower = strtolower(trim((string)$envFlag));
    return in_array($envFlagLower, ['1', 'true', 'yes', 'on', 'enabled'], true);
}
