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
            script {
                def snykHome = tool name: 'Orthia_Snyk', type: 'io.snyk.jenkins.tools.SnykInstallation'
                sh "${snykHome}/snyk code test --org=dutchnoud || true"
            }
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
