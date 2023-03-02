#!/bin/bash

:<<MARK

MARK

# 變量配置[START]
CURRENT_PATH=$(pwd)
APP_NAME=$(pwd)
APP_PATH=$(pwd)
APP_INFO=(
    "$APP_NAME 9501 $APP_PATH"
    # ...
)
# local / sandbox / test / product
RUNTIME_ENVI=${APP_ENV:-local}
# ...
# 避免同名函數
PREFIX="HANDLER"
# 變量配置[END]

function HANDLERcomposer () {
    option=$1
    case $option in
    "install" | "update" )
      cd skeleton && composer $option
      cd ${CURRENT_PATH}
      # composer.json裡面執行刪除會存在權限不足的情況（因：進程用戶）
      rm -rf libaray/vendor
      cp -r skeleton/vendor library/vendor
      ;;
    esac
}

function HANDLERstart() {
    ulimit -n 102400
    rm -rf runtime
    echo "${APP_NAME} is starting ..."
     echo 'php bin/hyperf.php start'
     php bin/hyperf.php start
#    echo 'php bin/hyperf.php server:watch'
#    php bin/hyperf.php server:watch
}

function HANDLERstop() {
    for ((index=0; index<${#APP_INFO[*]}; index+=1)); do
        eachAppPathInfo=(${APP_INFO[$index]})
        if [ ${eachAppPathInfo[0]} == $APP_NAME ];then
                cd ${eachAppPathInfo[2]}
                echo "${APP_NAME} is stopping ..."
                for eachProcessId in `lsof -i:${eachAppPathInfo[1]} | awk 'NR>1{print $2}'`
                do
                        kill -9 $eachProcessId
                        echo "kill -9 $eachProcessId success"
                done
                echo "${APP_NAME} has stopped ( success )"
                break
        fi
    done
    # gotask, start-----
#    for tempProcessId in `lsof -i:6001 | awk 'NR>1{print $2}'`
#    do
#        kill -9 $tempProcessId
#        echo "kill -9 $tempProcessId success"
#    done
    # gotask, end-----
}

function HANDLERrestart() {
    HANDLERstop
    HANDLERstart
}

"${PREFIX}${1}" $2