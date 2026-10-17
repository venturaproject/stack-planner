# Stage 1: build the React SPA as a static production bundle (no Vite dev
# server / HMR / unsafe-eval in production — see nginx.prod.conf).
FROM node:22-alpine AS frontend-build

WORKDIR /app
ENV CI=true
RUN corepack enable && corepack prepare pnpm@12.3.4 --activate
COPY frontend/package.json frontend/pnpm-lock.yaml frontend/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY frontend/ .
ARG VITE_BACKEND_URL
ARG VITE_PUBLIC_API_URL
RUN pnpm run build

# Stage 2: serve the built assets + proxy /api to the backend.
FROM nginx:alpine

COPY infrastructure/nginx/nginx.prod.conf /etc/nginx/conf.d/default.conf
COPY infrastructure/nginx/security-headers.prod.conf /etc/nginx/snippets/security-headers.prod.conf
COPY --from=frontend-build /app/dist /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
