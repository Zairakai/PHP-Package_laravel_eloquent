#!/usr/bin/env bats
#
# Integration Tests for Composer Scripts
#
# Tests:
# - Composer script availability
# - Dev tools integration
# - Package validation
#

# Load test helpers
load '../helpers/test_helper'

setup() {
    setup_test_env
    cd "$PROJECT_ROOT" || exit 1
}

teardown() {
    teardown_test_env
}

# ============================================================================
# Composer Scripts Availability Tests
# ============================================================================

@test "composer test script exists" {
    run php -r "
        \$json = json_decode(file_get_contents('composer.json'), true);
        echo isset(\$json['scripts']['test']) ? 'yes' : 'no';
    "
    [ "$output" = "yes" ]
}

@test "composer quality script exists" {
    run php -r "
        \$json = json_decode(file_get_contents('composer.json'), true);
        echo isset(\$json['scripts']['quality']) ? 'yes' : 'no';
    "
    [ "$output" = "yes" ]
}

@test "composer analyse script exists" {
    run php -r "
        \$json = json_decode(file_get_contents('composer.json'), true);
        echo isset(\$json['scripts']['analyse']) ? 'yes' : 'no';
    "
    [ "$output" = "yes" ]
}

@test "composer cs script exists" {
    run php -r "
        \$json = json_decode(file_get_contents('composer.json'), true);
        echo isset(\$json['scripts']['cs']) ? 'yes' : 'no';
    "
    [ "$output" = "yes" ]
}

@test "composer rector script exists" {
    run php -r "
        \$json = json_decode(file_get_contents('composer.json'), true);
        echo isset(\$json['scripts']['rector']) ? 'yes' : 'no';
    "
    [ "$output" = "yes" ]
}

# ============================================================================
# Dev Tools Integration Tests
# ============================================================================

@test "zairakai/laravel-dev-tools is installed" {
    assert_dir_exists "${PROJECT_ROOT}/vendor/zairakai/laravel-dev-tools"
}

@test "phpunit.xml is a regular file (not symlink)" {
    # phpunit.xml must be copied, not symlinked (XML relative paths issue)
    [ -f "${PROJECT_ROOT}/phpunit.xml" ]
    [ ! -L "${PROJECT_ROOT}/phpunit.xml" ]
}

# ============================================================================
# Git Integration Tests
# ============================================================================

@test "git repository exists" {
    assert_dir_exists "${PROJECT_ROOT}/.git"
}

# ============================================================================
# Package Validation Tests
# ============================================================================

@test "composer validate succeeds" {
    run composer validate --no-check-publish --no-check-all
    assert_success
}
