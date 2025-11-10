# Use Node.js for development
FROM node:20-alpine

# Set working directory
WORKDIR /app

# Copy package.json first to install dependencies
COPY Front-end/package*.json ./

RUN npm install

# Copy the rest of your React app
COPY Front-end ./

# Expose Vite dev server port
EXPOSE 5173

# Default command (docker-compose will run this)
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
