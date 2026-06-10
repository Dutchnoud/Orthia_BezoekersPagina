pipeline {
    agent any
    stages {
        stage('Code ophalen') {
            steps {
                echo 'Code wordt opgehaald van GitHub...'
                checkout scm
            }
        }
        stage('Snyk Code Scan') {
    steps {
        withCredentials([string(credentialsId: 'snyk-token-string', variable: 'SNYK_TOKEN')]) {
            sh "/var/jenkins_home/snyk auth $SNYK_TOKEN"
            sh "/var/jenkins_home/snyk code test || true"
        }
    }
}
        stage('Deploy naar Apache') {
            steps {
                sh 'cp -r * /var/www/html/'
            }
        }
    }
}
