def configEnv = [:]  // 定义一个全局 Map 变量用于跨阶段共享
pipeline {
    agent any
    
    environment {
        REGISTRY_URL = '907381039844.dkr.ecr.us-west-2.amazonaws.com'
        AWS_REGION = 'us-west-2'
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Get Commit Hash and Set Config') {
            steps {
                script {
                    // 获取当前分支名
                    def BRANCH_NAME = env.BRANCH_NAME
                    if (BRANCH_NAME == null || BRANCH_NAME == 'HEAD') {
                        BRANCH_NAME = sh(script: 'git branch --show-current', returnStdout: true).trim()
                    }
                    echo "Current branch: ${BRANCH_NAME}"
                    
                    // 根据分支设置CONFIG_ID
                    switch(BRANCH_NAME) {
                        case 'main':
                            configEnv.CONFIG_ID = '0564c860-5f91-49d0-998a-e737d98c7d3f'
                            configEnv.PROJECT_ENV = 'prod'
                            configEnv.NAMESPACE = 'prod'
                            configEnv.DEPLOYMENT = 'prod-coolpay-mis'
                            configEnv.IMAGE_NAME = 'prod/coolpay.mis'
                            break
                        case 'test':
                            configEnv.CONFIG_ID = '8df07bcc-4dd1-4eb4-a5e9-d95a386767ba'
                            configEnv.PROJECT_ENV = 'test'
                            configEnv.NAMESPACE = 'test-coolpay'
                            configEnv.DEPLOYMENT = 'test-coolpay-mis'
                            configEnv.IMAGE_NAME = 'test/coolpay-mis'
                            break
                    }
                    echo "Using config ID: ${configEnv.CONFIG_ID}"
                    
                    // 获取commit hash并设置镜像标签
                    def COMMIT_HASH = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    configEnv.IMAGE_TAG = "${REGISTRY_URL}/${configEnv.IMAGE_NAME}:${COMMIT_HASH}"
                    echo "Image tag will be: ${configEnv.IMAGE_TAG}"
                }
            }
        }

        stage('Configure Database') {
            steps {
                script {
                    configFileProvider([configFile(fileId: configEnv.CONFIG_ID, variable: 'DB_CONFIG')]) {
                        // 读取配置文件内容
                        def configContent = readFile(file: env.DB_CONFIG)
                        echo "Config content: ${configContent}"
                        
                        // 将配置内容转换为Map
                        def config = [:] // 创建空Map
                        configContent.split('\n').each { line ->
                            if (line.trim() && line.contains('=')) {
                                def parts = line.split('=', 2)
                                config[parts[0].trim()] = parts[1].trim()
                            }
                        }
                        
                        // 备份原始配置文件
                        sh 'cp config/database.php config/database.php.bak || true'
                        
                        // 创建临时替换脚本
                        writeFile file: 'replace.awk', text: '''
                        /\'mysql\'[[:space:]]*=>[[:space:]]*\\[/,/\\]/ {
                            if ($0 ~ /\'hostname\'/) {
                                sub(/\'hostname\'[[:space:]]*=>[[:space:]]*\'[^\']*\'/, "\'hostname\'     => \'"HOST"\'")
                            }
                            if ($0 ~ /\'hostport\'/) {
                                sub(/\'hostport\'[[:space:]]*=>[[:space:]]*\'[^\']*\'/, "\'hostport\'     => \'"PORT"\'")
                            }
                            if ($0 ~ /\'database\'/) {
                                sub(/\'database\'[[:space:]]*=>[[:space:]]*\'[^\']*\'/, "\'database\'     => \'"DATABASE"\'")
                            }
                            if ($0 ~ /\'username\'/) {
                                sub(/\'username\'[[:space:]]*=>[[:space:]]*\'[^\']*\'/, "\'username\'     => \'"USERNAME"\'")
                            }
                            if ($0 ~ /\'password\'/) {
                                sub(/\'password\'[[:space:]]*=>[[:space:]]*\'[^\']*\'/, "\'password\'     => \'"PASSWORD"\'")
                            }
                        }
                        { print }
                        '''

                        // 执行替换
                        sh """
                            awk -v HOST='${config.DB_HOST}' \
                                -v PORT='${config.DB_PORT}' \
                                -v DATABASE='${config.DB_DATABASE}' \
                                -v USERNAME='${config.DB_USERNAME}' \
                                -v PASSWORD='${config.DB_PASSWORD}' \
                                -f replace.awk config/database.php > config/database.php.tmp && \
                            mv config/database.php.tmp config/database.php
                        """

                        // 删除临时文件
                        sh 'rm replace.awk'

                        // 打印替换后的内容进行验证
                        sh 'cat config/database.php'
                    }
                }
            }
        }

        stage('Login to AWS') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'root-aws-credentail', // Jenkins 中配置的 Credentials ID
                    usernameVariable: 'AWS_ACCESS_KEY_ID',
                    passwordVariable: 'AWS_SECRET_ACCESS_KEY'
                )]) {
                    script {
                        sh """
                            aws configure set aws_access_key_id $AWS_ACCESS_KEY_ID
                            aws configure set aws_secret_access_key $AWS_SECRET_ACCESS_KEY
                            aws configure set region ${AWS_REGION}
                            aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${REGISTRY_URL}
                        """
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                script {
                    sh "docker build -f Dockerfile.${configEnv.PROJECT_ENV} -t ${configEnv.IMAGE_TAG} ."
                }
            }
        }
        
        stage('Push to ECR') {
            steps {
                script {
                    sh "docker push ${configEnv.IMAGE_TAG}"
                }
            }
        }

        stage('Update Kubernetes Deployment') {
            steps {
                withCredentials([file(credentialsId: 'kubeconfig-box-prod-cluster', variable: 'KUBECONFIG_FILE')]) {
                    script {
                        // 设置 KUBECONFIG 环境变量，避免写文件
                        withEnv(["KUBECONFIG=${KUBECONFIG_FILE}"]) {
                            sh """
                            kubectl -n ${configEnv.NAMESPACE} set image deployment/${configEnv.DEPLOYMENT} *=${configEnv.IMAGE_TAG}
                            kubectl -n ${configEnv.NAMESPACE} rollout status deployment/${configEnv.DEPLOYMENT}
                            """
                        }
                    }
                }
            }
        }
    }
    
    post {
        success {
            echo "Successfully built and pushed image: ${configEnv.IMAGE_TAG}"
            // 恢复原始配置文件
            sh 'mv config/database.php.bak config/database.php || true'
        }
        failure {
            echo 'Pipeline failed!'
            // 恢复原始配置文件
            sh 'mv config/database.php.bak config/database.php || true'
        }
    }
} 