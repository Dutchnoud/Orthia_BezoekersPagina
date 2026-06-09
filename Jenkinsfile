pipeline {
    agent any
    stages {
        stage('Code ophalen') {
            steps {
                echo 'Code wordt opgehaald van GitHub...'
                checkout scm
            }
        }
        stage('Deploy naar Apache') {
            steps {
                sh 'cp -r * /var/www/html/'
            }
        }
    }
}
