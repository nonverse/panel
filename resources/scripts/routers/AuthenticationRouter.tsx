import { Route, Routes, useNavigate } from 'react-router-dom';

import LoginContainer from '@/components/auth/LoginContainer';
import { NotFound } from '@/components/elements/ScreenBlock';

export default () => {
    const navigate = useNavigate();

    return (
        <div className="pt-8 xl:pt-32">
            <Routes>
                <Route path="login" element={<LoginContainer />} />
                <Route path="*" element={<NotFound onBack={() => navigate('/auth/login')} />} />
            </Routes>
        </div>
    );
};
