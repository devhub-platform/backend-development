/**
 * React/Next.js Component Example
 * Save as: components/NotificationTester.jsx
 * Usage: <NotificationTester />
 */

import React, { useState } from 'react';

export default function NotificationTester() {
  const [formData, setFormData] = useState({
    title: 'Test Notification',
    message: 'Hello from the API! 🚀 This is a test notification.',
    url: ''
  });

  const [response, setResponse] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [responseTime, setResponseTime] = useState(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setResponse(null);

    const startTime = Date.now();

    try {
      const payload = {
        title: formData.title,
        message: formData.message,
      };

      if (formData.url) {
        payload.url = formData.url;
      }

      const res = await fetch('/api/send-message_notification', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload)
      });

      const endTime = Date.now();
      setResponseTime(endTime - startTime);

      const data = await res.json();

      if (res.ok && data.success) {
        setResponse({ status: 'success', data });
      } else if (res.status === 422) {
        setResponse({ status: 'validation', data });
      } else {
        setError(data.error || data.message || 'Unknown error');
      }
    } catch (err) {
      setError(`Network Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={styles.container}>
      <div style={styles.card}>
        <h1 style={styles.title}>📨 Send Notification (React)</h1>
        <form onSubmit={handleSubmit}>
          <div style={styles.formGroup}>
            <label style={styles.label}>Title</label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              style={styles.input}
              required
            />
          </div>

          <div style={styles.formGroup}>
            <label style={styles.label}>Message</label>
            <textarea
              name="message"
              value={formData.message}
              onChange={handleChange}
              style={styles.textarea}
              required
            />
          </div>

          <div style={styles.formGroup}>
            <label style={styles.label}>URL (Optional)</label>
            <input
              type="url"
              name="url"
              value={formData.url}
              onChange={handleChange}
              style={styles.input}
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            style={{...styles.button, opacity: loading ? 0.6 : 1}}
          >
            {loading ? 'Sending...' : 'Send Notification'}
          </button>
        </form>
      </div>

      <div style={styles.card}>
        <h1 style={styles.title}>📊 Response</h1>

        {error && (
          <div style={{...styles.responseBox, ...styles.errorBox}}>
            <h3>❌ Error</h3>
            <p>{error}</p>
          </div>
        )}

        {response && response.status === 'success' && (
          <div style={{...styles.responseBox, ...styles.successBox}}>
            <h3>✓ Success</h3>
            <p><strong>Message:</strong> {response.data.message}</p>
            {response.data.notification_id && (
              <p><strong>Notification ID:</strong> {response.data.notification_id}</p>
            )}
            {response.data.recipients && (
              <p><strong>Recipients:</strong> {response.data.recipients}</p>
            )}
          </div>
        )}

        {response && response.status === 'validation' && (
          <div style={{...styles.responseBox, ...styles.errorBox}}>
            <h3>⚠️ Validation Error</h3>
            <ul>
              {Object.entries(response.data.errors || {}).map(([field, messages]) => (
                <li key={field}>
                  <strong>{field}:</strong> {Array.isArray(messages) ? messages.join(', ') : messages}
                </li>
              ))}
            </ul>
          </div>
        )}

        {response && (
          <div style={styles.stats}>
            <div>
              <strong>Response Time:</strong> {responseTime}ms
            </div>
            <div style={{marginTop: '10px'}}>
              <strong>Raw Response:</strong>
              <pre style={styles.pre}>
                {JSON.stringify(response.data, null, 2)}
              </pre>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

const styles = {
  container: {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '20px',
    padding: '20px',
    maxWidth: '1200px',
    margin: '0 auto',
  },
  card: {
    background: 'white',
    borderRadius: '10px',
    padding: '30px',
    boxShadow: '0 10px 30px rgba(0, 0, 0, 0.1)',
  },
  title: {
    fontSize: '24px',
    marginBottom: '20px',
    color: '#2d3748',
  },
  formGroup: {
    marginBottom: '20px',
  },
  label: {
    display: 'block',
    marginBottom: '8px',
    fontWeight: '600',
    color: '#2d3748',
  },
  input: {
    width: '100%',
    padding: '12px',
    border: '1px solid #e2e8f0',
    borderRadius: '8px',
    fontSize: '14px',
    fontFamily: 'inherit',
  },
  textarea: {
    width: '100%',
    padding: '12px',
    border: '1px solid #e2e8f0',
    borderRadius: '8px',
    fontSize: '14px',
    fontFamily: 'inherit',
    minHeight: '100px',
    resize: 'vertical',
  },
  button: {
    width: '100%',
    padding: '12px',
    background: '#667eea',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: '600',
    cursor: 'pointer',
  },
  responseBox: {
    padding: '15px',
    borderRadius: '8px',
    marginBottom: '15px',
  },
  successBox: {
    background: '#f0fdf4',
    borderLeft: '4px solid #48bb78',
    color: '#22543d',
  },
  errorBox: {
    background: '#fef2f2',
    borderLeft: '4px solid #f56565',
    color: '#742a2a',
  },
  stats: {
    padding: '15px',
    background: '#f7fafc',
    borderRadius: '8px',
  },
  pre: {
    background: 'white',
    padding: '10px',
    borderRadius: '4px',
    overflowX: 'auto',
    fontSize: '12px',
    marginTop: '10px',
  }
};

