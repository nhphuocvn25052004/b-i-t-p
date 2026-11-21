import React from 'react';
import ReactDOM from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import App from './App.jsx';
import './style.css';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    {/* HashRouter (#/create, #/edit/3) — không cần cấu hình rewrite trên web server */}
    <HashRouter>
      <App />
    </HashRouter>
  </React.StrictMode>
);
