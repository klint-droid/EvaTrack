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
                bat 'php -v'
                bat 'composer -v'
            }
        }

        stage('Backend - Install & Test') {
            steps {
                echo 'Installing Composer dependencies...'
                bat 'composer install --no-interaction --prefer-dist --optimize-autoloader'

                echo 'Preparing test configuration...'
                bat 'if not exist .env copy .env.example .env'
                bat 'php artisan key:generate --env=testing'

                echo 'Running PHPUnit tests...'
                bat 'php artisan test'
            }
        }

        stage('Verify Swagger Documentation') {
            steps {
                echo 'Checking API documentation compilation...'
                bat 'php artisan l5-swagger:generate'
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
