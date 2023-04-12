import crypto from 'crypto';

async function verifySignature(req) {
    const signature = crypto
        .createHmac('sha1', process.env.VERCEL_SIGNATURE)
        .update(req.body)
        .digest('hex');
        
    return signature === req.headers['x-vercel-signature'];
}

export const handler = async(event) => {
    
    if (! await verifySignature(event)) {
        return {
            statusCode: 403,
            body: 'Unauthorized.'
        }
    }

    const body = JSON.parse(event.body)
    const project = body.payload.project.id
    const environment = body.payload.deployment.meta.gitlabCommitRef

    // TODO: switch case to each project or something..
    // TODO: post request

    return {
        statusCode: 200,
        body: {},
    };
};

