pipeline {
    agent any
    environment {
        MAJOR_VERSION = '1'
    }
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
                    sh "/var/jenkins_home/snyk code test --report --project-name=Orthia_BezoekersPagina || true"
                }
            }
        }
        stage('Versie bijwerken') {
            steps {
                sh "sed -i 's/__MAJOR__/${MAJOR_VERSION}/' index.php"
                sh "sed -i 's/__VERSION__/${BUILD_NUMBER}/' index.php"
            }
        }
        stage('Deploy naar Apache') {
            steps {
                sh 'cp -r *.php /var/www/html/'
            }
        }
    }
}
