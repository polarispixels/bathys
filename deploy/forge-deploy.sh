# Bathys deploy script for Forge (zero-downtime deployments).
# Source of truth: deploy/forge-deploy.sh in the repository. Forge holds a copy;
# after editing this file, update the copy in Forge (see docs/deployment.md).
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

# Share storage/ across releases so logs and uploads survive deploys.
# Sites created in Forge's UI do this via "shared paths"; the API cannot
# set that, so it is done explicitly here.
SHARED=/home/forge/bathys.polarispixels.com/storage
mkdir -p $SHARED/app/public $SHARED/app/private $SHARED/framework/cache/data $SHARED/framework/sessions $SHARED/framework/views $SHARED/logs
rm -rf storage
ln -s $SHARED storage

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
