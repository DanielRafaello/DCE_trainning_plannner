FROM node:22-alpine

WORKDIR /app

# Install dependencies into the image. docker-compose mounts the source code over /app,
# but /app/node_modules is an anonymous volume, so it gets seeded from this layer
# (Linux binaries, not the ones from the Windows host).
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

EXPOSE 5173

# --host so the dev server listens on 0.0.0.0 and is reachable from the host machine
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
