// Test comment to trigger Jenkins build
pipeline {
    agent any


    environment {
        DB_CONNECTION = 'sqlite'
        DB_DATABASE   = ':memory:'
        APP_ENV       = 'testing'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Verify Environment') {
            steps {
                sh 'php -v'
                sh 'composer -v'
            }
        }

        stage('Backend - Install & Test') {
            steps {
                echo 'Installing Composer dependencies...'
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'

                echo 'Preparing test configuration...'
                sh 'cp -n .env.example .env || true'
                sh 'php artisan key:generate --env=testing'

                echo 'Running PHPUnit tests...'
                sh 'php artisan test'
            }
        }

        stage('Verify Swagger Documentation') {
            steps {
                echo 'Checking API documentation compilation...'
                sh 'php artisan l5-swagger:generate'
            }
        }
    }

    post {
        always {
            cleanWs()
        }
        success {
            echo 'Backend tests passed successfully!'
        }
        failure {
            echo 'Backend build/test failed!'
        }
    }
}
