pipeline {
    agent any

    environment {
        VM_STAGING_IP = '192.168.72.128'
        STAGING_USER = 'userfranco'
        STAGING_PATH = '/var/www/html'
        GITHUB_REPO = 'https://github.com/mcampos08/pagina.git'

        SONARQUBE_SERVER = 'SonarQube-Local'
        SYFT_OUTPUT = 'sbom.json'
        GRYPE_REPORT = 'grype-report.json'
        GRYPE_SARIF = 'grype-report.sarif'
        
        // Configuración específica para la aplicación vulnerable
        VULNERABLE_APP_PATH = 'vulnerable-app'
    }

    stages {

        // MÓDULO 1: ANÁLISIS DEL CÓDIGO
        stage('📥 Clonar código') {
            steps {
                echo 'Clonando repositorio desde GitHub...'
                git url: "${GITHUB_REPO}", branch: 'main'
                
                // Verificar estructura del proyecto
                sh '''
                    echo "=== Estructura del proyecto ==="
                    ls -la
                    echo "=== Contenido de vulnerable-app ==="
                    ls -la ${VULNERABLE_APP_PATH}/
                '''
            }
        }

        stage('📦 Validar dependencias PHP') {
            steps {
                echo 'Verificando archivos PHP en vulnerable-app...'
                sh '''
                    echo "Archivos PHP encontrados:"
                    find ${VULNERABLE_APP_PATH} -name "*.php" -type f
                    
                    # Verificar si existe composer.json
                    if [ -f "${VULNERABLE_APP_PATH}/composer.json" ]; then
                        echo "Composer.json encontrado, instalando dependencias..."
                        cd ${VULNERABLE_APP_PATH}
                        composer install --no-interaction --prefer-dist
                    else
                        echo "No se encontró composer.json, saltando instalación de dependencias"
                    fi
                '''
            }
        }

        stage('🔍 Análisis SAST con SonarQube') {
            steps {
                script {
                    def scannerHome = tool 'SonarQubeScanner'
                    withSonarQubeEnv("${SONARQUBE_SERVER}") {
                        sh """
                            ${scannerHome}/bin/sonar-scanner \
                                -Dsonar.projectKey=pagina-vulnerable-app \
                                -Dsonar.projectName='Pagina Vulnerable App' \
                                -Dsonar.sources=${VULNERABLE_APP_PATH} \
                                -Dsonar.language=php \
                                -Dsonar.exclusions=**/*.log,**/*.md,**/uploads/**,**/config.php.bak \
                                -Dsonar.php.coverage.reportPaths=coverage.xml \
                                -Dsonar.sourceEncoding=UTF-8
                        """
                    }
                }
            }
        }

        // MÓDULO 2: ANÁLISIS DE DEPENDENCIAS
        stage('🧬 Generar SBOM con Syft') {
            steps {
                echo 'Generando SBOM para toda la aplicación...'
                sh """
                    echo "Generando SBOM para el proyecto completo..."
                    syft dir:. -o json > ${SYFT_OUTPUT}
                    
                    echo "Resumen del SBOM generado:"
                    cat ${SYFT_OUTPUT} | jq '.artifacts | length' || echo "SBOM generado correctamente"
                """
            }
        }

        stage('🧪 Escaneo de vulnerabilidades con Grype') {
            steps {
                echo 'Ejecutando análisis de vulnerabilidades...'
                sh """
                    set +e
                    
                    echo "Ejecutando Grype para detectar vulnerabilidades..."
                    grype sbom:${SYFT_OUTPUT} -o json > ${GRYPE_REPORT}
                    grype sbom:${SYFT_OUTPUT} -o sarif > ${GRYPE_SARIF}
                    
                    echo "Mostrando resumen de vulnerabilidades:"
                    grype sbom:${SYFT_OUTPUT} -o table
                    
                    echo "Verificando vulnerabilidades críticas..."
                    grype sbom:${SYFT_OUTPUT} -o table --fail-on high
                    
                    if [ \$? -ne 0 ]; then
                        echo "BUILD_SHOULD_FAIL=true" > grype.fail
                        echo "⚠️  Vulnerabilidades críticas detectadas"
                    else
                        echo "✅ No se encontraron vulnerabilidades críticas"
                    fi
                    
                    set -e
                """
            }
        }

        // MÓDULO 3: DESPLIEGUE A VM-STAGING
        stage('🔗 Prueba de conexión SSH') {
            steps {
                echo 'Validando conexión a máquina de staging...'
                script {
                    sshagent(['staging-ssh-key']) {
                        sh """
                            ssh -o StrictHostKeyChecking=no ${STAGING_USER}@${VM_STAGING_IP} \\
                            'echo "[SSH OK] \$(hostname) - \$(date)"; uptime; df -h /var/www/html'
                        """
                    }
                }
            }
        }

        stage('🚀 Despliegue en Staging') {
            steps {
                echo 'Realizando despliegue en servidor de staging...'
                script {
                    sshagent(['staging-ssh-key']) {
                        sh """
                            echo "Sincronizando archivos con el servidor..."
                            
                            # Crear backup del directorio actual
                            ssh ${STAGING_USER}@${VM_STAGING_IP} \\
                            'if [ -d "${STAGING_PATH}/vulnerable-app" ]; then 
                                sudo cp -r ${STAGING_PATH}/vulnerable-app ${STAGING_PATH}/vulnerable-app.backup.\$(date +%Y%m%d_%H%M%S)
                            fi'
                            
                            # Sincronizar archivos (excluyendo .git y otros)
                            rsync -avz --delete \\
                                --exclude='.git' \\
                                --exclude='*.log' \\
                                --exclude='node_modules' \\
                                --exclude='.env' \\
                                ./ ${STAGING_USER}@${VM_STAGING_IP}:${STAGING_PATH}/
                            
                            # Configurar permisos
                            ssh ${STAGING_USER}@${VM_STAGING_IP} \\
                            'sudo chown -R www-data:www-data ${STAGING_PATH}/vulnerable-app/uploads && 
                             sudo chmod -R 755 ${STAGING_PATH}/vulnerable-app && 
                             sudo chmod -R 777 ${STAGING_PATH}/vulnerable-app/uploads'
                        """
                    }
                }
            }
        }

        stage('✅ Verificación del despliegue') {
            steps {
                echo 'Verificando que el despliegue fue exitoso...'
                script {
                    sshagent(['staging-ssh-key']) {
                        sh """
                            ssh ${STAGING_USER}@${VM_STAGING_IP} \\
                            'echo "=== Verificación del despliegue ===" && 
                             ls -la ${STAGING_PATH}/ && 
                             echo "=== Archivos en vulnerable-app ===" && 
                             ls -la ${STAGING_PATH}/vulnerable-app/ && 
                             echo "=== Estado del servicio Apache ===" && 
                             sudo systemctl status apache2 --no-pager -l'
                        """
                    }
                }
            }
        }
    }

    post {
        always {
            echo 'Archivando artefactos y reportes...'
            
            // Archivar reportes de seguridad
            archiveArtifacts artifacts: '*.json', fingerprint: true, allowEmptyArchive: true
            
            // Procesar reportes SARIF si existen
            script {
                if (fileExists("${GRYPE_SARIF}")) {
                    recordIssues(tools: [sarif(pattern: "${GRYPE_SARIF}")])
                }
            }

            // Verificar si hay vulnerabilidades críticas
            script {
                if (fileExists('grype.fail')) {
                    currentBuild.result = 'FAILURE'
                    echo '❌ Vulnerabilidades críticas detectadas. Build marcado como FALLIDO.'
                    
                    // Enviar notificación (opcional)
                    emailext(
                        subject: "🚨 Vulnerabilidades críticas - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                        body: """
                        Se detectaron vulnerabilidades críticas en el proyecto.
                        
                        Proyecto: ${env.JOB_NAME}
                        Build: #${env.BUILD_NUMBER}
                        
                        Revisar los reportes adjuntos para más detalles.
                        """,
                        to: "admin@clindata.com",
                        attachmentsPattern: "*.json"
                    )
                } else {
                    echo '✅ Análisis Grype aprobado - No hay vulnerabilidades críticas.'
                }
            }
        }

        success {
            echo '🎉 PIPELINE COMPLETO - DESPLIEGUE EXITOSO'
            echo """
            ================================================
            ✅ DESPLIEGUE COMPLETADO EXITOSAMENTE
            ================================================
            🌐 URL: http://${VM_STAGING_IP}/vulnerable-app/
            📁 Ruta: ${STAGING_PATH}/vulnerable-app/
            🔧 Usuario: ${STAGING_USER}
            ================================================
            """
        }

        failure {
            echo '🚨 ERROR EN ALGUNA ETAPA - REVISAR LOGS'
            echo """
            ================================================
            ❌ PIPELINE FALLÓ
            ================================================
            📋 Revisar los logs de Jenkins para más detalles
            🔍 Verificar conectividad SSH
            🛠️  Comprobar configuración de herramientas
            ================================================
            """
        }

        unstable {
            echo '⚠️  PIPELINE INESTABLE - REVISAR ADVERTENCIAS'
        }

        cleanup {
            echo '🧹 Limpiando workspace...'
            cleanWs()
        }
    }
}
