<?php
namespace Deployer;

require 'magento2.php';

// Project name
set('application', 'cxashop');

// Project repository
set('repository', 'git@github.com:cxagroup/cxa-marketplace.git');

set('writable_use_sudo', true);

set('keep_releases', 1);

//set('http_user', 'ci-user');

//set('http_group', 'apache');

set('writable_mode', 'chmod');

set('shared_files',
[
'app/etc/env.php',
'app/etc/config.php',
'pub/.htaccess',
'.htaccess',
]);

// Hosts
//SG
localhost('sg')
->stage('dev')
->set('deploy_path', '/var/www/cxashopdev')
->user('ci-user')
->set('http_user', 'ci-user')
->set('http_group', 'apache')
;

localhost('sg')
->stage('test')
->set('deploy_path', '/var/www/cxashoptest')
->user('cxashoptest')
->set('http_user', 'cxashoptest')
->set('http_group', 'apache')
;

//HK
localhost('hk')
->stage('dev')
->set('deploy_path', '/var/www/cxashopdevhk')
->user('ci-user')
->set('http_user', 'ci-user')
->set('http_group', 'apache')
;

localhost('hk')
->stage('test')
->set('deploy_path', '/var/www/cxashoptesthk')
->user('cxashoptest')
->set('http_user', 'cxashoptest')
->set('http_group', 'apache')
;

//MY
localhost('my')
->stage('dev')
->set('deploy_path', '/var/www/cxashopdevmy')
->user('ci-user')
->set('http_user', 'ci-user')
->set('http_group', 'apache')
;

localhost('my')
->stage('test')
->set('deploy_path', '/var/www/cxashoptestmy')
->user('cxashoptest')
->set('http_user', 'cxashoptest')
->set('http_group', 'apache')
;

desc('Deploy assets');
task('magento:deploy:assets', function () {
try {
runLocally("{{bin/php}} {{release_path}}/bin/magento setup:static-content:deploy en_US --theme Magento/backend --theme Cxa/theme-default");
} catch (\RuntimeException $e) {
writeln('Error in compilation');
}
});

desc('Upgrade magento database');
task('magento:setup:upgrade', function () {
runLocally("{{bin/php}} {{release_path}}/bin/magento setup:upgrade --keep-generated");
});

desc('setup dir composer');
task('deploy:setup_vendors', function () {
runLocally('cd {{release_path}}/update && composer install');
});

desc('production mode');
task('magento:setup:production', function () {
runLocally('cd {{release_path}} && {{bin/php}} bin/magento deploy:mode:set production --skip-compilation');
});

desc('reset permissions');
task('deploy:reset_permissions', function () {
runLocally('sudo chcon -R --type=httpd_sys_rw_content_t {{release_path}}/var {{release_path}}/pub');
});

desc('Magento2 deployment operations');
task('deploy:magento', [
'magento:cache:flush',
'magento:setup:upgrade',
'magento:compile',
'magento:deploy:assets',
//'magento:maintenance:enable',
'magento:cache:flush',
'magento:setup:production',
//'magento:maintenance:disable'
]);

task('deploy:restartweb', function () {
runLocally('sudo service httpd restart');
});

desc('Deploy project');
task('deploy', [
'deploy:info',
'deploy:prepare',
//'deploy:lock',
'deploy:release',
'deploy:update_code',
'deploy:shared',
'deploy:writable',
'deploy:vendors',
'deploy:setup_vendors',
'deploy:clear_paths',
'deploy:magento',
'deploy:symlink',
'deploy:reset_permissions',
//'deploy:unlock',
'cleanup',
'success'
]);

after('success','deploy:restartweb');
