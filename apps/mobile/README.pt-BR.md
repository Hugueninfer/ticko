# apps/mobile — app do cliente Ticko

App React Native + **Expo SDK 56** para clientes: abrir tickets com fotos, acompanhar a
conversa com a equipe em tempo real e receber notificações push.

> 🇺🇸 English: [README.md](README.md)

## Rodar no celular (Expo Go)

Precisa de: a stack Docker, Node 22 e um celular com o **Expo Go** (SDK 56) no **mesmo Wi-Fi**
do computador.

```bash
make up-lan       # a stack acessível pela rede local (só a porta do web fica exposta)
make demo         # uma vez: tenant "acme" com cliente e tickets
make mobile-dev   # cria apps/mobile/.env com o IP da sua máquina e inicia o Expo
```

Leia o QR code com o Expo Go (Android) ou a câmera (iOS) e toque em **Entrar como cliente
demo** (só aparece quando a API tem `TICKO_DEMO_LOGIN=true`; o app não leva senha nenhuma).

O que o `make up-lan` muda: o Caddy (`WEB_PORT`, por exemplo 8090) passa a ouvir em `0.0.0.0`
em vez de `127.0.0.1`, e os aparelhos da rede alcançam o web e a API. Postgres, Redis e Reverb
continuam em redes internas do Docker. Para voltar a só localhost: `make up`. Se o celular não
conectar, libere a porta no firewall (ex.: `sudo ufw allow 8090/tcp`) e confira se o Wi-Fi não
isola os aparelhos.

`apps/mobile/.env` (fora do git, ver [.env.example](.env.example)):

| Variável | |
| --- | --- |
| `EXPO_PUBLIC_API_URL` | `http://<IP da LAN>:<WEB_PORT>` ou um túnel HTTPS. HTTP só é aceito em desenvolvimento e só para endereço da rede local; build de release exige HTTPS. |
| `EXPO_PUBLIC_TENANT` | Slug do tenant (`acme` na demo). |
| `EAS_PROJECT_ID` | Opcional; necessário para push num development build. |

Valores `EXPO_PUBLIC_*` vão compilados no app: **nunca coloque segredo neles**.

### Sem Wi-Fi em comum: túnel

```bash
cloudflared tunnel --url http://localhost:8090      # mostra https://<aleatorio>.trycloudflare.com
# apps/mobile/.env: EXPO_PUBLIC_API_URL=https://<aleatorio>.trycloudflare.com
corepack pnpm --filter @ticko/mobile start --tunnel
```

A URL do túnel fica pública enquanto ele roda: use só dados de demo e desligue ao terminar.

### Testar o tempo real

Abra um ticket no celular e responda como atendente no web (http://localhost:8090, botão
"Atendente"): a resposta e a linha do tempo do status atualizam no celular sozinhas.

### Notificações push

Desde o SDK 53 o **Expo Go não recebe push remoto no Android** (e o Expo recomenda development
build no iOS também); a tela de perfil avisa isso. O lado do servidor está pronto e testado.
Para receber push no celular, use um development build:

1. Crie um projeto Expo (`npx eas init`) e coloque o id em `EAS_PROJECT_ID`.
2. Android: configure as credenciais FCM no EAS (`eas credentials`).
3. `npx eas build --profile development --platform android` (ou `npx expo run:android`) e instale.
4. Entre e permita notificações. Respostas e mudanças de status chegam como "Nova resposta no
   ticket #XXXXXX", nunca com o conteúdo do ticket.

## Segurança

- Token da API só no `expo-secure-store` (Keychain / Keystore, só neste aparelho) e em
  memória, nunca no AsyncStorage (o lint proíbe). Expira (30 dias por padrão) e é revogado no
  servidor ao sair, o que também desliga o push do aparelho.
- Um 401 da API desconecta o app.
- Deep links (`ticko://tickets/<id>`) e notificações só aceitam id de ticket válido; a tela
  busca o ticket pela API, que confere o acesso de novo.
- Fotos re-codificadas em JPEG no celular (sem EXIF/GPS, até 2048 px) e de novo na API.
- Sem `console` (lint): nada pessoal vai para o log do aparelho.
