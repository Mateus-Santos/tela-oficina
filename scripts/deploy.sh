#!/usr/bin/env bash
# Um comando no jai para validar imagens, autorizar e publicar o commit de origin/master.
set -Eeuo pipefail

REPO=Mateus-Santos/tela-oficina
WORKFLOW=deploy.yml

if [[ "${1:-}" == --help ]]; then
  echo 'Uso: bash scripts/deploy.sh  (valida e publica a master com confirmacao)'
  exit 0
fi
[[ $# -eq 0 ]] || { echo 'Argumento invalido. Use --help.' >&2; exit 2; }

for bin in git gh; do command -v "$bin" >/dev/null || { echo "Necessario: $bin" >&2; exit 1; }; done
cd "$(git rev-parse --show-toplevel)"
[[ "$(git remote get-url origin)" == *Mateus-Santos/tela-oficina* ]] || {
  echo 'Origin nao e o repositorio SOS Mecanica; deploy bloqueado.' >&2; exit 1;
}
# O deploy usa exclusivamente origin/master. Alteracoes locais nao sao publicadas
# e nao bloqueiam um hotfix ja aprovado em master.
gh auth status >/dev/null

git fetch --no-tags origin master
SHA="$(git rev-parse origin/master)"
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || exit 1

printf 'Vai validar e publicar a master %s na VPS.\n' "$SHA"
read -r -p 'Para confirmar, digite PUBLICAR: ' CONFIRMACAO
[[ "$CONFIRMACAO" == PUBLICAR ]] || { echo 'Cancelado.'; exit 1; }

ultimo_run() {
  gh run list --repo "$REPO" --workflow "$WORKFLOW" --branch master \
    --event workflow_dispatch --limit 20 --json databaseId \
    --jq '.[0].databaseId // 0'
}

aguardar_id() {
  local anterior="$1" id i
  for (( i=0; i<45; i++ )); do
    id="$(gh run list --repo "$REPO" --workflow "$WORKFLOW" --branch master \
      --event workflow_dispatch --limit 20 --json databaseId,headSha \
      --jq ".[] | select(.headSha == \"$SHA\" and .databaseId > $anterior) | .databaseId" | head -n 1)"
    if [[ "$id" =~ ^[0-9]+$ ]]; then printf '%s\n' "$id"; return 0; fi
    sleep 2
  done
  echo 'Nao foi possivel identificar execucao nova no GitHub.' >&2
  return 1
}

conferir_sucesso() {
  local id="$1" retorno commit
  retorno="$(gh api "repos/$REPO/actions/runs/$id" --jq '.conclusion')"
  commit="$(gh api "repos/$REPO/actions/runs/$id" --jq '.head_sha')"
  [[ "$retorno" == success && "$commit" == "$SHA" ]] || {
    echo "Execucao $id nao aprovada: resultado=$retorno commit=$commit" >&2; exit 1;
  }
}

ANTES="$(ultimo_run)"
echo '========== 1/2. VALIDAR E CONSTRUIR IMAGENS =========='
gh workflow run "$WORKFLOW" --repo "$REPO" --ref master -f modo=validar
RUN_VALIDAR="$(aguardar_id "$ANTES")"
gh run watch "$RUN_VALIDAR" --repo "$REPO" --exit-status
conferir_sucesso "$RUN_VALIDAR"

# Bloqueia se a master mudou durante a validacao.
git fetch --no-tags origin master
[[ "$(git rev-parse origin/master)" == "$SHA" ]] || {
  echo 'Master mudou durante a validacao: deploy bloqueado.' >&2; exit 1;
}

ANTES="$(ultimo_run)"
echo '========== 2/2. PUBLICAR NA VPS =========='
gh workflow run "$WORKFLOW" --repo "$REPO" --ref master \
  -f modo=publicar -f confirmacao=PUBLICAR
RUN_PUBLICAR="$(aguardar_id "$ANTES")"

# Respeita a protecao de environment do GitHub. Um unico comando faz a
# aprovacao se a conta tiver permissao, sem desligar a protecao existente.
for (( i=0; i<90; i++ )); do
  ESTADO="$(gh api "repos/$REPO/actions/runs/$RUN_PUBLICAR" --jq '.status')"
  [[ "$ESTADO" != completed ]] || break
  if [[ "$ESTADO" == waiting ]]; then
    ENV_ID="$(gh api "repos/$REPO/actions/runs/$RUN_PUBLICAR/pending_deployments" \
      --jq '.[] | select(.environment.name == "production") | .environment.id' | head -n 1)"
    if [[ "$ENV_ID" =~ ^[0-9]+$ ]]; then
      if gh api --method POST "repos/$REPO/actions/runs/$RUN_PUBLICAR/pending_deployments" \
         -F "environment_ids[]=$ENV_ID" -f state=approved \
         -f comment="Deploy confirmado pelo terminal para $SHA" >/dev/null; then
        echo 'Aprovacao production registrada.'
        break
      else
        echo "Aprovacao automatica nao permitida. Revise: https://github.com/$REPO/actions/runs/$RUN_PUBLICAR" >&2
        exit 1
      fi
    fi
  fi
  [[ "$ESTADO" != failure && "$ESTADO" != cancelled ]] || break
  sleep 2
done

gh run watch "$RUN_PUBLICAR" --repo "$REPO" --exit-status
conferir_sucesso "$RUN_PUBLICAR"
echo "DEPLOY APROVADO: $SHA"
echo "GitHub Actions: https://github.com/$REPO/actions/runs/$RUN_PUBLICAR"
