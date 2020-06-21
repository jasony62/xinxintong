const baseApi =
  (process.env.VUE_APP_API_SERVER || '') + '/rest/site/fe/user/notice'

export default function create(tmsAxios) {
  return {
    uncloseList(siteId, missionId, batchArg) {
      const { page, size } = batchArg
      const params = { site: siteId, mission: missionId, page, size }
      return tmsAxios.get(`${baseApi}/uncloseList`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    list(siteId, missionId, batchArg) {
      const { page, size } = batchArg
      const params = { site: siteId, mission: missionId, page, size }
      return tmsAxios.get(`${baseApi}/list`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    close(siteId, noticeId) {
      const params = { site: siteId, id: noticeId }
      return tmsAxios.get(`${baseApi}/close`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    count(siteId) {
      const params = { site: siteId }
      return tmsAxios.get(`${baseApi}/count`, { params }).then((rst) => {
        return rst.data.data
      })
    },
  }
}
