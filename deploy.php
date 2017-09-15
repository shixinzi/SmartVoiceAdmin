<?php
namespace Deployer;

require 'recipe/laravel.php';

// Configuration

set('repository', 'git@github.com:superwen/SmartVoiceAdmin.git');
set('git_tty', true);
add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);
set('allow_anonymous_stats', false);

// Hosts

host('120.27.239.106')
    ->port('56413')
    ->user('chenshengwen')
    ->stage('prod')
    ->set('deploy_path', '/hwdata/www/SmartVoiceAdmin');

// Tasks

desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    run('sudo systemctl restart php-fpm.service');
});



after('deploy:symlink', 'php-fpm:restart');

after('deploy:failed', 'deploy:unlock');

before('deploy:symlink', 'artisan:migrate');

