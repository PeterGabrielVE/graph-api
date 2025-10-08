# Graph API - Proyecto de Nodos Jerárquicos

API para gestionar nodos jerárquicos (árbol de nodos), con soporte para creación, listado, consulta de hijos por profundidad y eliminación de nodos.

---

## 📦 Requisitos

- Docker ≥ 20.10
- Docker Compose ≥ 1.29
- Opcional: Postman  para probar la API

---

## 🏗️ Despliegue rápido con Docker

1. **Levantar servicios:**

```bash
docker-compose up -d --build

2. **Instalar dependencias de Composer (si no se copiaron en la imagen)**
docker exec -it graph-api-apok composer install

3. ** Ejecutar migraciones de base de datos: **
docker exec -it graph-api-apok php artisan migrate

4. ** Opcional: Poblar datos iniciales**
docker exec -it graph-api-apok php artisan db:seed

URLS
| Laravel API | [http://localhost:8000/api/v1](http://localhost:8000/api/v1)                       |
| Swagger UI  | [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation) |
| phpMyAdmin  | [http://localhost:8080](http://localhost:8080)                                     |
