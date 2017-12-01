#!/bin/bash

function pull_request() {
    echo "Pull Request, Skipping Deploy"
}

function package() {
    echo "Prepping a build"

    mkdir /tmp/build
    cp -r * /tmp/build
    cd /tmp/build
    rm -rf .git
    cd -
    cd /tmp
    tar -czvf /tmp/package.tgz /tmp/build
    cd -
}

function submit() {
    echo "Submitting"
    echo "EDIT YOUR TRAVIS SCRIPTS TO ENABLE THIS"

    # S_HOST=$3

    # Copy our deploy script to the remote machine
    # scp -o "StrictHostKeyChecking no" -i /tmp/deploy_rsa -P ${S_PORT} ./scripts/deploy.sh ${S_USER}@${S_HOST}:/tmp/deploy.sh
    # scp -o "StrictHostKeyChecking no" -i /tmp/deploy_rsa -P ${S_PORT} /tmp/package.tgz ${S_USER}@${S_HOST}:/tmp/package.tgz

    # run
    # ssh -o "StrictHostKeyChecking no" -i /tmp/deploy_rsa -p${S_PORT} ${S_USER}@${S_HOST} chmod +x /tmp/deploy.sh
    # ssh -o "StrictHostKeyChecking no" -i /tmp/deploy_rsa -p${S_PORT} ${S_USER}@${S_HOST} /tmp/deploy.sh dev
}

function deploy_dev() {
    echo "Deploying Dev Branch to Staging"
    echo "EDIT YOUR TRAVIS FILE TO ENABLE THIS"

    # package
    # submit ${DEPLOY_PORT} ${DEPLOY_USER} ${DEPLOY_HOST}
}

function deploy_prod() {
    echo "Deploying Master Branch to Production"
    echo "EDIT YOUR TRAVIS FILE TO ENABLE THIS"

    # package
    # submit ${LIVE_DEPLOY_PORT} ${LIVE_DEPLOY_USER} ${LIVE_DEPLOY_HOST}
}

if [ "$TRAVIS_BRANCH" == "dev" ]; then
  deploy_dev
elif [ "$TRAVIS_BRANCH" == "master" ]; then
  deploy_prod
else
  echo "Not a deployment branch"
fi
