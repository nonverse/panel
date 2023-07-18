import {useEffect, useState} from 'react';
import useFlash from '@/plugins/useFlash';
import http from "@/api/http";
import Spinner from "@/components/elements/Spinner";
import NotFoundSvg from '@/assets/images/not_found.svg';
import ScreenBlock from "@/components/elements/ScreenBlock";

function LoginContainer() {

    const {clearFlashes} = useFlash();
    const query = new URLSearchParams(window.location.search)
    const [authorized, setAuthorized] = useState(true)

    useEffect(() => {
        clearFlashes();

        async function login() {
            await http.post('/auth/login', {
                code: query.get('code')
            })
                .then(response => {
                    if (response.data.success) {
                        // @ts-ignore
                        window.location = "/"
                    }
                })
                .catch(e => {
                    if (e.response.status === 401) {
                        window.location = e.response.data.data.auth_url
                    }
                    if (e.response.status === 403) {
                        setAuthorized(false)
                    }
                })
        }

        login()
    }, []);

    return (
        <>
            {authorized ? (
                <div className="awaiting-oauth absolute w-screen h-screen top-0" style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center"
                }}>
                    <Spinner/>
                </div>
            ) : <ScreenBlock title="Forbidden" image={NotFoundSvg} message="You are not authorized to use this application"/>
            }
        </>
    );
}

export default LoginContainer;
