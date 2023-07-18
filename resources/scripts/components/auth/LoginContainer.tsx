import { useEffect } from 'react';
import useFlash from '@/plugins/useFlash';

function LoginContainer() {

    const { clearFlashes } = useFlash();

    useEffect(() => {
        clearFlashes();
    }, []);

    return (
        <div>Awaiting OAuth2 Authorization</div>
    );
}

export default LoginContainer;
