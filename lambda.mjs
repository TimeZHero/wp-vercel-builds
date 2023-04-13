import crypto from 'crypto';
import https from 'https';


function performRequest(options, body) {
  return new Promise((resolve, reject) => {
    const req = https.request(options, (res) => {
      console.log(`statusCode: ${res.statusCode}`);

      let result = ''

      res.on('data', (chunk) => {
        result += chunk;
      });

      res.on('end', () => resolve(result))
    });

    req.on('error', (error) => {
      console.error(error);
      reject(error)
    });

    req.write(body);
    req.end();
  })
}

async function verifySignature(req) {
  const signature = crypto
    .createHmac('sha1', process.env.VERCEL_SIGNATURE)
    .update(req.body)
    .digest('hex');

  return signature === req.headers['x-vercel-signature'];
}

export const handler = async (event) => {

  if (! await verifySignature(event)) {
    console.log('Error: Request not from Vercel')

    return {
      statusCode: 403,
      body: {
        message: 'Unauthorized.'
      }
    }
  }

  const body = JSON.parse(event.body)
  const project = body.payload.project.id
  const environment = body.payload.deployment.meta.gitlabCommitRef
  const projects = {
    'myProjectKey': {
      'main': 'mysite.com',
      'preprod': 'preprod.mysite.com'
    }
  }
  const domain = projects[project][environment]

  if (!domain) {
    console.log('No domain was found. Maybe you need to update the lambda?')

    return {
      statusCode: 404,
      body: {}
    }
  }

  const options = {
    hostname: domain,
    port: 443,
    path: '/wp-json/builds/update',
    method: 'POST',
    headers: {
      'x-vercel-signature': event.headers['x-vercel-signature']
    }
  };

  console.log(await performRequest(options, event.body))
};
