# Graph API - Proyecto de Nodos Jerárquicos

API para gestionar nodos jerárquicos (árbol de nodos), con soporte para creación, listado, consulta de hijos por profundidad y eliminación de nodos.

---

## 📦 Requisitos

- Docker ≥ 20.10
- Docker Compose ≥ 1.29
- Opcional: Postman  para probar la API

# Despliegue rápido con Docker

1. Clonar el repositorio:
   git clone https://github.com/PeterGabrielVE/graph-api.git
2. Levantar contenedores:
   docker-compose up -d --build
3. Ejecutar migraciones:
   docker exec -it graph-api-apok php artisan migrate
4. (Opcional) Poblar datos:
   docker exec -it graph-api-apok php artisan db:seed
5. Acceder a la API:
   http://localhost:8000/api/v1
6. Acceder a Swagger:
   http://localhost:8000/api/documentation
7. Acceder a phpMyAdmin:
   http://localhost:8080
                      |
