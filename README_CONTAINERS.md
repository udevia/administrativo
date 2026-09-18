# Despliegue en Contenedor (Docker / Portainer) — mi ERP

El proyecto incluye todo lo necesario para correr en contenedores: `Dockerfile`,
`docker-compose.yml` y un provisionamiento automático (base de datos + migraciones
+ usuario admin en el primer arranque, sin pasar por el Setup Wizard).

## Arquitectura del stack

| Servicio | Imagen            | Función                                                        | Puerto |
|----------|-------------------|----------------------------------------------------------------|--------|
| `app`    | `mi-erp:latest`   | PHP 8.2 + Apache (front controller en `public/`)               | 8080:80 |
| `db`     | `mariadb:10.11`   | Base de datos (volumen `db_data` persistente)                  | interno |

Volúmenes persistentes:
- `db_data` — datos de MariaDB.
- `app_config` — `config/` (credenciales generadas).
- `app_storage` — `storage/` (sello de instalación, respaldos, licencias, logs).

## Variables de entorno

Definir en `.env` (ver `.env.example`) o directamente en el stack de Portainer:

| Variable            | Por defecto        | Descripción                                        |
|---------------------|--------------------|----------------------------------------------------|
| `DB_PASSWORD`       | `mi_erp_seguro`    | Contraseña del usuario de BD `mi_erp`              |
| `DB_ROOT_PASSWORD`  | `root_seguro`      | Contraseña root de MariaDB                         |
| `ADMIN_USER`        | `admin`            | Usuario administrador (solo primer arranque)       |
| `ADMIN_PASSWORD`    | `admin123`         | Contraseña admin (solo primer arranque)            |
| `ADMIN_NAME`        | `Administrador General` | Nombre del admin                              |

> El admin y la BD solo se crean en el **primer arranque** (cuando no existen
> `config/database.php` y `storage/installed.lock`). Cambiar esas variables
> después de la primera instalación NO reescribe el admin: cambie la contraseña
> dentro del sistema.

## Opción A — Desplegar desde Portainer (recomendado)

1. **Clonar el repositorio en el host Docker** (o en cualquier carpeta del host
   que Portainer administre). Puede hacerse desde *Portainer → Web Console → Shell*:
   ```bash
   git clone https://github.com/udevia/administrativo.git /opt/mi-erp
   cd /opt/mi-erp
   ```
2. **Construir la imagen** (una sola vez por versión):
   ```bash
   docker build -t mi-erp:latest .
   ```
3. **Crear el stack:** Portainer → *Stacks* → *Add stack* → nombre `mi-erp` →
   pegar el contenido de `docker-compose.yml` (o seleccionar la carpeta
   `/opt/mi-erp` si usa "Create stack from files") → *Deploy the stack*.
4. **Acceder:** `http://<IP-del-host>:8080` con `admin` / `admin123`
   (o las variables que haya definido) y cambiar la contraseña en la primera sesión.

### Actualizar a una nueva versión
```bash
cd /opt/mi-erp && git pull
docker build -t mi-erp:latest .
```
Luego en Portainer: *Stacks → mi-erp → Update stack* (re-despliega la imagen
nueva). Las migraciones nuevas se ejecutan automáticamente si el entrypoint lo
detecta… **oportunidad:** si el repositorio trae migraciones nuevas y el sistema
ya estaba instalado, ejecutar una vez en el shell de Portainer:
```bash
docker exec mi_erp_app php run_migrations.php
```

### Respaldo / restauración
```bash
# Respaldo de la BD
docker exec mi_erp_db mariadb-dump -umi_erp -p'mi_erp_seguro' mi_admin_system > respaldo_$(date +%F).sql
# Respaldo de volúmenes (app)
docker run --rm -v mi_erp_app_config:/cfg -v mi_erp_app_storage:/sto -v $(pwd):/out alpine \
  tar czf /out/config_storage.tar.gz -C / cfg sto
```

### Licencia
La imagen incluye la licencia demo `license/sistema.lic`. Para producción,
anexar en el servicio `app` del compose un bind mount de su `.lic` real:
```yaml
volumes:
  - /ruta/en/el/host/sistema.lic:/var/www/html/license/sistema.lic:ro
```

## Opción B — Docker Compose directo (sin Portainer)

```bash
git clone https://github.com/udevia/administrativo.git && cd administrativo
cp .env.example .env        # y ajuste las contraseñas
docker compose up -d --build   # construye la imagen automáticamente
```
Acceder en `http://localhost:8080`.

## Notas

- El primer arranque demora unos segundos extra (el entrypoint espera a que
  MariaDB pase el healthcheck y ejecuta las 28 migraciones).
- Los módulos que llaman a APIs externas (WhatsApp, Telegram, pasarelas C2P)
  requieren salida a internet desde el host Docker.
- Las vistas cargan Tailwind/Alpine/FontAwesome desde CDN: el **navegador** del
  usuario necesita internet.
- Para depurar: `docker logs mi_erp_app` y `docker logs mi_erp_db`.
- El shell interno: `docker exec -it mi_erp_app php` (CLI para pruebas,
  `php tools/smoke_test_mi.php`, etc.).
