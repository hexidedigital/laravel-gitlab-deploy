# PHP, Laravel development
_artisan()
{
    local arg="${COMP_LINE#php }"

    case "$arg" in
        a*)
            COMP_WORDBREAKS=${COMP_WORDBREAKS//:}
            COMMANDS=`artisan --raw --no-ansi list | sed "s/[[:space:]].*//g"`
            COMPREPLY=(`compgen -W "$COMMANDS" -- "${COMP_WORDS[COMP_CWORD]}"`)
            ;;
        *)
            COMPREPLY=( $(compgen -o default -- "${COMP_WORDS[COMP_CWORD]}") )
            ;;
        esac

    return 0
}

complete -F _artisan artisan
complete -F _artisan a

alias artisan="{{BIN_PHP}} artisan"
alias a="artisan"

alias pcomopser="{{BIN_COMPOSER}}"

export BASE_DIR="{{DEPLOY_BASE_DIR}}"
export CURRENT_DIR="$BASE_DIR/current"
export SHARE_DIR="$BASE_DIR/share"

alias cur="cd $CURRENT_DIR"
alias share="cd $SHARE_DIR"
