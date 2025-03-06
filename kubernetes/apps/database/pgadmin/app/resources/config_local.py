AUTHENTICATION_SOURCES = ['oauth2', 'internal']
OAUTH2_AUTO_CREATE_USER = True
OAUTH2_CONFIG = [{
    'OAUTH2_NAME': '${SECRET_DOMAIN}',
    'OAUTH2_DISPLAY_NAME': 'Authentik',
    'OAUTH2_CLIENT_ID': os.getenv('OAUTH2_CLIENT_ID'),
    'OAUTH2_CLIENT_SECRET': os.getenv('OAUTH2_CLIENT_SECRET'),
    'OAUTH2_TOKEN_URL': 'https://authentik.${SECRET_DOMAIN}/application/o/token/',
    'OAUTH2_AUTHORIZATION_URL': 'https://authentik.${SECRET_DOMAIN}/application/o/authorize/',
    'OAUTH2_API_BASE_URL': 'https://authentik.${SECRET_DOMAIN}/',
    'OAUTH2_USERINFO_ENDPOINT': 'https://authentik.${SECRET_DOMAIN}/application/o/userinfo/',
    'OAUTH2_SERVER_METADATA_URL': 'https://authentik.${SECRET_DOMAIN}/application/o/pgadmin/.well-known/openid-configuration',
    'OAUTH2_SCOPE': 'openid email profile',
    'OAUTH2_ICON': '',
    'OAUTH2_BUTTON_COLOR': '#e54c3c'
}]
