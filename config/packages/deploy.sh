server {
    server_name new.e-tawjihi.ma www.new.e-tawjihi.ma;

    root /var/www/new.e-tawjihi.ma/public_html;
    index index.html;

    location / {
        try_files $uri /index.html;
    }

    # (Optionnel) Gzip pour optimiser la vitesse de chargement
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    gzip_min_length 256;

    # (Optionnel) Cache statique
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }



}
server {

}

