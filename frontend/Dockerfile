FROM node:22-alpine

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Clean install dependencies
RUN rm -rf node_modules package-lock.json && npm install && npm cache clean --force

# Create app directory with proper permissions
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nuxt -u 1001 && \
    chown -R nuxt:nodejs /app

# Switch to non-root user
USER nuxt

# Copy application files (using volume mount in docker-compose)
# COPY --chown=nuxt:nodejs . .

# Expose port
EXPOSE 3000

# Environment variables
ENV NUXT_HOST=0.0.0.0
ENV NUXT_PORT=3000

# Start application
CMD ["npm", "run", "dev"]