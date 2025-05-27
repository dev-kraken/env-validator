<?php

namespace Tests\Feature;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/env-sync-command-tests-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    $this->envPath = $this->tempDir.'/.env';
    $this->examplePath = $this->tempDir.'/.env.example';
});

afterEach(function () {
    if (file_exists($this->envPath)) {
        unlink($this->envPath);
    }
    if (file_exists($this->examplePath)) {
        unlink($this->examplePath);
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

test('env:sync --check command shows synchronized status', function () {
    $content = "APP_NAME=TestApp\nAPP_ENV=testing";
    file_put_contents($this->envPath, $content);
    file_put_contents($this->examplePath, $content);

    $this->artisan('env:sync', [
        '--check' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔍 Checking environment file synchronization...')
        ->assertExitCode(0);
});

test('env:sync --check command detects missing keys', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nNEW_KEY=value");
    file_put_contents($this->examplePath, 'APP_NAME=TestApp');

    $this->artisan('env:sync', [
        '--check' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔍 Checking environment file synchronization...')
        ->assertExitCode(1); // Out of sync exit code
});

test('env:sync --check command detects missing .env file', function () {
    $this->artisan('env:sync', [
        '--check' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔍 Checking environment file synchronization...')
        ->assertExitCode(1); // FAILURE exit code
});

test('env:sync command synchronizes files successfully', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nNEW_KEY=value");
    file_put_contents($this->examplePath, 'APP_NAME=TestApp');

    $this->artisan('env:sync', [
        '--force' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔄 Synchronizing environment files...')
        ->expectsOutput('🎉 Synchronization completed successfully!')
        ->assertExitCode(0);

    // Verify the file was updated
    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('NEW_KEY=');
});

test('env:sync command with --remove-extra flag removes extra keys', function () {
    file_put_contents($this->envPath, 'APP_NAME=TestApp');
    file_put_contents($this->examplePath, "APP_NAME=TestApp\nEXTRA_KEY=value");

    $this->artisan('env:sync', [
        '--force' => true,
        '--remove-extra' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔄 Synchronizing environment files...')
        ->assertExitCode(0);

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->not()->toContain('EXTRA_KEY');
});

test('env:sync command with --no-values flag creates empty values', function () {
    file_put_contents($this->envPath, 'NEW_KEY=some_value');

    $this->artisan('env:sync', [
        '--force' => true,
        '--no-values' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->assertExitCode(0);

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('NEW_KEY=')
        ->and($exampleContent)->not()->toContain('NEW_KEY=some_value');
});

test('env:sync command creates .env.example from scratch', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nAPP_ENV=testing");

    $this->artisan('env:sync', [
        '--force' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔄 Synchronizing environment files...')
        ->assertExitCode(0);

    expect(file_exists($this->examplePath))->toBeTrue();

    $exampleContent = file_get_contents($this->examplePath);
    expect($exampleContent)->toContain('# Environment Configuration Example')
        ->and($exampleContent)->toContain('APP_NAME=your_value_here');
});

test('env:sync command fails when .env does not exist', function () {
    $this->artisan('env:sync', [
        '--force' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔄 Synchronizing environment files...')
        ->assertExitCode(1);
});

test('env:sync command shows preview without --force flag', function () {
    file_put_contents($this->envPath, "APP_NAME=TestApp\nNEW_KEY=value");
    file_put_contents($this->examplePath, 'APP_NAME=TestApp');

    $this->artisan('env:sync', [
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('🔄 Synchronizing environment files...')
        ->expectsOutput('📋 Preview of changes:')
        ->expectsQuestion('Do you want to proceed with the synchronization?', false)
        ->assertExitCode(1);
});

test('env:sync command handles already synchronized files', function () {
    $content = "APP_NAME=TestApp\nAPP_ENV=testing";
    file_put_contents($this->envPath, $content);
    file_put_contents($this->examplePath, $content);

    $this->artisan('env:sync', [
        '--force' => true,
        '--env-path' => $this->envPath,
        '--example-path' => $this->examplePath,
    ])
        ->expectsOutput('✅ Files are already synchronized!')
        ->assertExitCode(0);
});
