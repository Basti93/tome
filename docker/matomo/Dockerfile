FROM matomo:latest

# Install netcat for connection testing
RUN apt-get update && apt-get install -y netcat-traditional && rm -rf /var/lib/apt/lists/*

# Copy initialization script
COPY docker/matomo/docker-entrypoint.sh /

# Make it executable
RUN chmod +x /docker-entrypoint.sh

# Use custom entrypoint
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
