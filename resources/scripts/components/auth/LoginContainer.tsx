import { useEffect } from 'react';
import useFlash from '@/plugins/useFlash';
import http from "@/api/http";
import Spinner from "@/components/elements/Spinner";
import {useNavigate} from "react-router-dom";

function LoginContainer() {

    const { clearFlashes } = useFlash();
    const query = new URLSearchParams(window.location.search)
    const navigate = useNavigate()

    useEffect(() => {
        clearFlashes();

        async function login() {
            await http.post('/auth/login', {
                code: query.get('code')
            })
                .then(response => {
                    if (response.data.success) {
                        window.location = "/"
                    }
                })
                .catch(e => {
                    if (e.response.status === 401) {
                        window.location = e.response.data.data.auth_url
                    }
                })
        }

        login()
    }, []);

    return (
        <div className="awaiting-oauth absolute w-screen h-screen top-0" style={{
            display: "flex",
            alignItems: "center",
            justifyContent: "center"
        }}>
            <Spinner/>
        </div>
    );
}

export default LoginContainer;
